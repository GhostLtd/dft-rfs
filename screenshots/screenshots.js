#!/usr/bin/env node
const yargs = require('yargs/yargs')
const {hideBin} = require('yargs/helpers')

const {run: domesticRun} = require('./src/domestic')
const {run: internationalRun} = require('./src/international')
const {run: preEnquiryRun} = require('./src/pre-enquiry')
const {run: roroRun} = require('./src/roro')

const generateCommand = (yargs, isRoro) => {
    // noinspection BadExpressionStatementJS
    yargs
        .positional('baseUrl', {
            description: 'The root URL of the DfT RFS website',
            type: 'string'
        }).positional('outputDir', {
            description: 'Output directory',
            type: 'string',
        });

    if (isRoro) {
        yargs
            .option('email', {
                alias: 'e',
                type: 'string',
                description: 'Email',
                default: 'screenshot@example.com',
            })
            .option('login-link', {
                alias: 'l',
                type: 'string',
                description: 'Login link',
                default: 'https://example.com/do-login?signature=123',
            })
            .demandOption(['email'])
            .demandOption(['login-link'])
    } else {
        yargs
            .option('username', {
                alias: 'u',
                type: 'string',
                description: 'Username',
                default: '00000000',
            })
            .option('password', {
                alias: 'p',
                type: 'string',
                description: 'Password',
            })
            .option('location-mode', {
                type: 'string',
                default: 'place-name',
                hidden: true  // Auto-detected from feature flag, passed by PHP
            })
            .option('ni-border-crossing-only', {
                type: 'boolean',
                default: false,
                hidden: true
            })
            .demandOption(['password'])
    }

    yargs
        .strictCommands()
        .strictOptions();
}

// noinspection BadExpressionStatementJS
yargs(hideBin(process.argv))
    .usage('usage: $0 <command>')
    .command('domestic <baseUrl> <outputDir>', 'Generates screenshots of the domestic website', yargs => generateCommand(yargs, false), argv => domesticRun(argv))
    .command('international <baseUrl> <outputDir>', 'Generates screenshots of the international website', yargs => generateCommand(yargs, false), argv => internationalRun(argv))
    .command('pre-enquiry <baseUrl> <outputDir>', 'Generates screenshots of the pre-enquiry website', yargs => generateCommand(yargs, false), argv => preEnquiryRun(argv))
    .command('roro <baseUrl> <outputDir>', 'Generates screenshots of the roro website', yargs => generateCommand(yargs, true), argv => roroRun(argv))
    .demandCommand(1)
    .help()
    .argv