<?php

namespace App\Command;

use App\Utility\Domestic\DvlaImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PrivateBetaPrepFilterDvlaFilesCommand extends Command
{
    protected static $defaultName = 'app:private-beta-prep:filter-dvla-files';
    private $dvlaImporter;
    /**
     * @var SymfonyStyle
     */
    private $io;

    public function __construct(DvlaImporter $dvlaImporter)
    {
        $this->dvlaImporter = $dvlaImporter;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Filter the DVLA files supplied by Lucy, for the private beta')
            ->addArgument('sample_filter_csv_file', InputArgument::REQUIRED, 'The sample CSV file whcih will be used to filter the DVLA files')
            ->addArgument('source_dvla_files', InputArgument::IS_ARRAY, 'The DVLA source files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $filterFile = $input->getArgument('sample_filter_csv_file');
        $sourceFiles = $input->getArgument('source_dvla_files');

        if (count($sourceFiles) <= 0) {
            $this->io->error('Select the right files');
            return 1;
        }

        $this->initialSummary($filterFile, $sourceFiles);
        $filterData = $this->loadFilterFile($filterFile);
        $sourceData = $this->loadSourceFiles($sourceFiles);
//        dump($sourceData);

        $filteredSourceData = $this->getFilteredSourceData($filterData, $sourceData);
        file_put_contents(pathinfo($filterFile, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . "output_data.txt", implode(PHP_EOL, $filteredSourceData));

//        dump(memory_get_peak_usage()/1024/1024);

        $this->io->success('You have a new command! Now make it your own! Pass --help to see your options.');
        return 0;
    }

    protected function getFilteredSourceData($filterData, $sourceData) {
        $filteredSourceData = [];
        foreach ($filterData as $filterItem) {
            $regMark = $filterItem[0];
            if (!isset($sourceData[$regMark])) {
                $this->io->error("Missing Reg: ${regMark}");
                continue;
            }
            $filteredSourceData[$regMark] = $sourceData[$regMark];
        }
        return $filteredSourceData;
    }

    protected function loadSourceFiles($sourceFiles) {
        $sourceData = [];
        foreach ($sourceFiles as $sourceFile) {
            $fileData = trim(file_get_contents($sourceFile));
            $sourceData = array_merge($sourceData, explode(PHP_EOL, $fileData));
        }

        return array_combine(
            array_map(fn($x) => trim(substr($x, 0, 7)), $sourceData),
            $sourceData
        );
    }

    protected function loadFilterFile($filterFile, $skipFirstLine = true) {
        $fileData = trim(file_get_contents($filterFile));
        $lines = explode(PHP_EOL, $fileData);
        $csvData = array();
        if ($skipFirstLine) {
            unset($lines[0]);
        }
        foreach ($lines as $line) {
            $csvData[] = str_getcsv($line);
        }
        return $csvData;
    }

    protected function initialSummary($filterFile, $sourceFiles) {
        $initialSummary = "Using {$filterFile} to filter\n";
        foreach ($sourceFiles as $sourceFile) {
            $initialSummary .= " - {$sourceFile}\n";
        }
        $this->io->block($initialSummary, null, "fg=green");
    }

//    private function rubbish() {
//        $sourceData = [];
//        foreach ($sourceFiles as $sourceFile) {
//            $sourceData = array_merge($sourceData, $this->dvlaImporter->getDataFromFilename($sourceFile));
//        }
////        dump(count($sourceData));
//        $indexedSourceData = [];
//        foreach ($sourceData as $k=>$v) {
//            $indexedSourceData[$v['reg_mark']] = $v;
//            unset($sourceData[$k]);
//        }
//    }
}
