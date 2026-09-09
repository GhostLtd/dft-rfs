<?php

namespace App\Command\Dev;

use App\Utility\OutwardCodes\OutwardCodeChecker;
use App\Utility\OutwardCodes\OnspdReader;
use App\Utility\OutwardCodes\OutwardCodeCounts;
use App\Utility\OutwardCodes\RangeCollapser;
use App\Utility\PostcodeHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Builds the UK outward-code data file used to validate the first part of a postcode.
 *
 * Source: the ONS Postcode Directory (ONSPD), published quarterly by the ONS Open Geography Portal.
 * The default URL is the ArcGIS item-data link for the August 2026 release; each new release is a
 * new item, so pass --url (or download the ZIP and pass --input) when updating to a later one.
 *
 * Output format (one line per postcode area, comment lines start with "#"):
 *
 *   BN1-3,BN5-6,BN8-27,BN41-45,BN50-52,BN88,BN91,BN99
 *
 * where BN1-3 stands for BN1, BN2 and BN3. Letter-suffixed districts (SW1A, E1W, ...) are listed
 * individually. Every outward code in the ONSPD is included, whether or not its postcodes are still
 * live (respondents may well quote a postcode from older paperwork), minus the deliberately excluded
 * codes listed in EXCLUDED_OUTWARD_CODES. Pass --live-only to drop districts with no live postcodes.
 */
#[AsCommand(
    name: 'rfs:dev:build-outward-codes',
    description: 'Build the UK outward-code (postcode district) data file from the ONS Postcode Directory (ONSPD)',
)]
class BuildOutwardCodesCommand extends Command
{
    public const string DEFAULT_OUTPUT = OutwardCodeChecker::DATA_FILE;

    /**
     * Outward codes deliberately left out of the data file. This is intentional, not an omission.
     *
     * GIR: the outward code of GIR 0AA, the old Girobank postcode (Girobank was sold to Alliance & Leicester in
     *      1990 and is now part of Santander).
     *      It is the only UK outward code with no digit, so the app's postcode regex (PostcodeHelper::getRegex)
     *      rejects it regardless, and it is a non-geographic postcode that no goods-vehicle trip starts or
     *      ends at. Listing it would only make the data file disagree with the validator.
     *
     * NPT: the original Newport postcode area, replaced by NP in 1984. All 2,417 NPT postcodes in the ONSPD were
     *      terminated between December 1981 and December 1984, so they have been dead for over 40 years. It also
     *      has three letters and no digit, so the app's postcode regex rejects it regardless.
     *
     * Every other outward code in the ONSPD, including the non-geographic "large user" districts such as
     * BN99 or EC1P, is kept: those appear on real business paperwork and a respondent may well enter one.
     */
    private const array EXCLUDED_OUTWARD_CODES = ['GIR', 'NPT'];

    // ONSPD August 2026 (released 2026-09-01), via the ONS Open Geography Portal
    private const string DEFAULT_URL = 'https://www.arcgis.com/sharing/rest/content/items/9e5a92a3cfb14dc7ad43d6ea7a7b8c7f/data';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OnspdReader $reader,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('input', 'i', InputOption::VALUE_REQUIRED,
                'Read a locally downloaded ONSPD ZIP (or extracted CSV) instead of downloading')
            ->addOption('url', null, InputOption::VALUE_REQUIRED,
                'URL of the ONSPD ZIP to download (ignored when --input is given)', self::DEFAULT_URL)
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED,
                'Where to write the data file (relative paths are resolved against the project directory)', self::DEFAULT_OUTPUT)
            ->addOption('live-only', null, InputOption::VALUE_NONE,
                'Only include outward codes with at least one live postcode (by default, districts whose postcodes have all been terminated are included too)');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $outputPath = (string) $input->getOption('output');
        if (!str_starts_with($outputPath, '/')) {
            $outputPath = "{$this->projectDir}/{$outputPath}";
        }

        $inputPath = $input->getOption('input');
        $downloaded = null;

        try {
            if ($inputPath === null) {
                try {
                    $inputPath = $downloaded = $this->download((string) $input->getOption('url'), $io);
                } catch (HttpClientException | \RuntimeException $e) {
                    $io->error([
                        "Could not download the ONSPD: {$e->getMessage()}",
                        'Download the ONSPD ZIP manually from the ONS Open Geography Portal and re-run with --input /path/to/ONSPD.zip',
                    ]);
                    return Command::FAILURE;
                }
            }

            try {
                $counts = $this->reader->read(
                    (string) $inputPath,
                    fn(string $source) => $io->writeln("Reading <info>{$source}</info>", OutputInterface::VERBOSITY_VERBOSE),
                    fn(int $rows) => $io->writeln(sprintf('  %s rows', number_format($rows)), OutputInterface::VERBOSITY_VERY_VERBOSE),
                );
            } catch (\RuntimeException $e) {
                $io->error($e->getMessage());
                return Command::FAILURE;
            }
        } finally {
            if ($downloaded !== null) {
                @unlink($downloaded);
            }
        }

        $liveOnly = (bool) $input->getOption('live-only');
        $candidates = $liveOnly ? $counts->liveOutwardCodes() : $counts->allOutwardCodes();

        $excluded = array_values(array_intersect($candidates, self::EXCLUDED_OUTWARD_CODES));
        $codes = array_values(array_diff($candidates, $excluded));
        if ($excluded) {
            $io->writeln('Excluding outward code(s) listed in EXCLUDED_OUTWARD_CODES: <comment>' . implode(', ', $excluded) . '</comment>');
        }

        // Every remaining outward code must be accepted by the app's postcode regex, or the data file would list
        // codes the validator rejects before it ever consults the file. GIR is the only known exception and is
        // excluded above; anything else here means Royal Mail has issued a new format and the regex needs updating.
        $unmatched = array_values(array_filter($codes, fn(string $code): bool => !preg_match(PostcodeHelper::getRegex(true), $code)));
        if ($unmatched) {
            $io->error([
                'Outward code(s) in the source do not match the app\'s postcode regex (PostcodeHelper::getRegex): ' . implode(', ', $unmatched),
                'The regex is probably no longer fit for purpose and needs updating before this data file can be rebuilt. '
                . 'If a code is genuinely one the app should never accept, add it to EXCLUDED_OUTWARD_CODES with a comment saying why.',
            ]);
            return Command::FAILURE;
        }

        $collapsed = RangeCollapser::collapse($codes);
        $tokenCount = array_sum(array_map(count(...), $collapsed));

        (new Filesystem())->dumpFile($outputPath, $this->header($counts, $liveOnly, $excluded) . RangeCollapser::format($collapsed));

        $io->success([
            "Wrote {$outputPath}",
            sprintf(
                '%s outward codes (%s live, %s terminated-only in source) as %s entries across %s postcode areas',
                number_format(count($codes)),
                number_format(count($counts->liveOutwardCodes())),
                number_format(count($counts->terminatedOutwardCodes())),
                number_format($tokenCount),
                number_format(count($collapsed)),
            ),
        ]);

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $excluded
     */
    private function header(OutwardCodeCounts $counts, bool $liveOnly, array $excluded): string
    {
        $year = $this->releaseYear($counts->sources) ?? date('Y');

        return implode("\n", [
            '# UK outward codes (the district part of a postcode, e.g. BN1 in "BN1 1AA")',
            '# Source: ' . implode(', ', $counts->sources) . ' (ONS Postcode Directory)',
            '# Built by: bin/console ' . $this->getName() . ($liveOnly ? ' --live-only' : ''),
            $liveOnly
                ? '# Contents: live outward codes only (those with at least one live postcode)'
                : '# Contents: every outward code in the source, including those whose postcodes have all been terminated',
            '# Format: one line per postcode area; a range such as BN1-4 means BN1, BN2, BN3 and BN4',
            ...($excluded ? ['# Excluded on purpose (see BuildOutwardCodesCommand::EXCLUDED_OUTWARD_CODES): ' . implode(', ', $excluded)] : []),
            '#',
            '# Licence: Open Government Licence v3.0. Attribution required by the ONS wherever this data is used:',
            "#   Contains OS data \u{a9} Crown copyright and database right {$year}",
            "#   Contains Royal Mail data \u{a9} Royal Mail copyright and database right {$year}",
            "#   Source: Office for National Statistics licensed under the Open Government Licence v.3.0",
            '# Northern Ireland postcodes (BT) are covered by a separate Northern Ireland End User Licence (internal business use only).',
            '# See https://www.ons.gov.uk/methodology/geography/licences',
        ]) . "\n";
    }

    /**
     * The release year from an ONSPD file name such as "ONSPD_AUG_2026_UK.csv", used in the attribution statements.
     *
     * @param list<string> $sources
     */
    private function releaseYear(array $sources): ?string
    {
        foreach ($sources as $source) {
            if (preg_match('/ONSPD_[A-Z]{3}_(\d{4})/i', $source, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /**
     * Streams the ZIP to a temporary file and returns its path.
     */
    private function download(string $url, SymfonyStyle $io): string
    {
        $io->writeln("Downloading <info>{$url}</info>");

        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['User-Agent' => 'dft-rfs outward-code builder'],
        ]);

        $status = $response->getStatusCode();
        if ($status !== 200) {
            $response->cancel();
            throw new \RuntimeException("HTTP {$status} from {$url}");
        }

        $tmp = tempnam(sys_get_temp_dir(), 'onspd_');
        if ($tmp === false) {
            throw new \RuntimeException('Could not create a temporary file');
        }
        $handle = fopen($tmp, 'wb');
        if ($handle === false) {
            throw new \RuntimeException("Could not open temporary file {$tmp} for writing");
        }

        $length = (int) ($response->getHeaders(false)['content-length'][0] ?? 0);
        $progress = new ProgressBar($io, $length);
        $progress->setFormat($length > 0 ? ProgressBar::getFormatDefinition('normal') : ProgressBar::getFormatDefinition('normal_nomax'));

        try {
            foreach ($this->httpClient->stream($response) as $chunk) {
                if ($chunk->isTimeout()) {
                    continue;
                }
                $content = $chunk->getContent();
                if ($content !== '') {
                    fwrite($handle, $content);
                    $progress->advance(strlen($content));
                }
            }
            $progress->finish();
            $io->newLine();
        } catch (\Throwable $e) {
            fclose($handle);
            @unlink($tmp);
            throw $e;
        }
        fclose($handle);

        return $tmp;
    }
}
