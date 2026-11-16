// Summarises `yarn npm audit --all --recursive --json` for the CI job summary (see docs/CI.md).
//
// Usage: node yarn-audit-summary.mjs <label> <audit-json-file> <audit-exit-status>
//
// The yarn audit is report-only: advisories are listed but don't fail the job. What does fail it is an audit that
// didn't run (a non-zero exit with no advisories, or output that can't be parsed), so that a broken audit never looks
// like "no vulnerabilities".
import { appendFileSync, readFileSync } from 'node:fs';

const [label, file, statusArg] = process.argv.slice(2);
if (label === undefined || file === undefined || statusArg === undefined) {
    console.error('usage: yarn-audit-summary.mjs <label> <audit-json-file> <audit-exit-status>');
    process.exit(2);
}
const status = Number(statusArg);

const fail = (message) => {
    console.error(`::error title=yarn npm audit (${label})::${message}`);
    process.exit(1);
};

const advisories = [];
const lines = readFileSync(file, 'utf8').split('\n').filter((line) => line.trim() !== '');
for (const line of lines) {
    let entry;
    try {
        entry = JSON.parse(line);
    } catch {
        fail(`audit did not run: unparseable output: ${line.slice(0, 200)}`);
    }
    // Yarn's own messages (as opposed to advisories): {"type": "info"|"warning"|"error", "data": "..."}
    if (entry?.type === 'info' || entry?.type === 'warning') {
        console.log(`yarn: ${entry.data ?? line}`);
        continue;
    }
    if (entry?.type === 'error') {
        fail(`audit did not run: ${entry.data ?? line}`);
    }
    const details = entry?.children;
    if (typeof entry?.value !== 'string' || typeof details?.Severity !== 'string') {
        fail(`audit did not run: unexpected output: ${line.slice(0, 200)}`);
    }
    advisories.push({
        package: entry.value,
        severity: details.Severity,
        issue: String(details.Issue ?? ''),
        url: String(details.URL ?? ''),
        versions: String(details['Vulnerable Versions'] ?? ''),
    });
}

if (status !== 0 && advisories.length === 0) {
    fail(`audit did not run (exit status ${status}, no advisories reported)`);
}

const order = ['critical', 'high', 'moderate', 'low', 'info'];
const rank = (severity) => (order.includes(severity) ? order.indexOf(severity) : order.length);
advisories.sort((a, b) => rank(a.severity) - rank(b.severity) || a.package.localeCompare(b.package));

const counts = {};
for (const { severity } of advisories) {
    counts[severity] = (counts[severity] ?? 0) + 1;
}
const countText = Object.keys(counts)
    .sort((a, b) => rank(a) - rank(b))
    .map((severity) => `${counts[severity]} ${severity}`)
    .join(', ');

const cell = (text) => text.replaceAll('|', '\\|').replaceAll('\n', ' ');
let summary = `### yarn npm audit: ${label}\n\n`;
if (advisories.length === 0) {
    summary += 'No advisories.\n\n';
} else {
    summary += `${advisories.length} advisories: ${countText}. Report-only (see docs/CI.md).\n\n`;
    summary += '| Severity | Package | Vulnerable versions | Issue |\n|---|---|---|---|\n';
    for (const a of advisories) {
        summary += `| ${a.severity} | ${cell(a.package)} | ${cell(a.versions)} | [${cell(a.issue)}](${a.url}) |\n`;
    }
    summary += '\n';
}

console.log(summary);
if (process.env.GITHUB_STEP_SUMMARY) {
    appendFileSync(process.env.GITHUB_STEP_SUMMARY, summary);
}
if (advisories.length > 0) {
    console.log(`::warning title=yarn npm audit (${label})::${advisories.length} advisories: ${countText}`);
}
