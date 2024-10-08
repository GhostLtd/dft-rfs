var Encore = require('@symfony/webpack-encore');
const path = require("path");

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
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/js/app.js')
    .addEntry('admin', './assets/js/admin.js')
    .addEntry('pdf', './assets/styles/pdf.scss')
    // .addEntry('govuk-frontend-bundle', './bundles/Ghost/GovUkFrontendBundle/Resources/assets/js/bundle.js')
    //.addEntry('page1', './assets/page1.js')
    //.addEntry('page2', './assets/page2.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    // .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    // .enableSingleRuntimeChunk()
    .disableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // enables Sass/SCSS support
    .enableSassLoader((options) => {
        // Silence the noise complaining about primitive division/multiplication usage in govuk-frontend:
        // https://frontend.design-system.service.gov.uk/importing-css-assets-and-javascript/#silence-deprecation-warnings-from-dependencies-in-dart-sass
        options.sassOptions = options.sassOptions || {};
        options.sassOptions.quietDeps = true
    })

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // uncomment if you use API Platform Admin (composer req api-admin)
    //.enableReactPreset()
    //.addEntry('admin', './assets/admin.js')


    .copyFiles({
        from: 'assets/icons',
        to: 'icons/[name].[ext]',
    })

    .copyFiles({
        from: './node_modules/govuk-frontend/dist/govuk/assets/images',
        to: 'images/[name].[ext]',
    })
;

var config = Encore.getWebpackConfig();

let govukAssetsPath = path.resolve('./node_modules/govuk-frontend/dist/govuk/assets/');

for(let rule of config.module.rules) {
    if (rule.test.toString().match(/s\[ac]ss/)) {
        for(let one of rule.oneOf) {
            for(let tool of one.use) {
                if (tool.loader.includes('/sass-loader/')) {
                    tool.options.additionalData = '$govuk-assets-path: "' + govukAssetsPath + '/";';
                }
            }
        }
    }
}

module.exports = config;
