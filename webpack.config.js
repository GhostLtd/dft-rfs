const Encore = require('@symfony/webpack-encore');
const configureGovukEncore = require('@ghost/govuk-frontend-bundle/encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

// Since we're actually running two websites, there's no advantage to split chunking (actually disadvantage)
if (!Encore.isProduction()) {
    Encore.splitEntryChunks();
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')

    .addEntry('app', './assets/js/app.js')
    .addEntry('admin', './assets/js/admin.js')
    .addEntry('pdf', './assets/styles/pdf.scss')

    .disableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .copyFiles({
        from: 'assets/icons',
        to: 'icons/[name].[ext]',
    })
;

// Shared GOV.UK build config: babel targets + polyfills, autoprefixer, sass options,
// govuk-frontend static assets. Browser policy lives in the bundle's .browserslistrc.
configureGovukEncore(Encore);

module.exports = Encore.getWebpackConfig();
