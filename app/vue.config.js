module.exports = {
    runtimeCompiler: true,
    productionSourceMap: false,
    configureWebpack:{
        optimization: {
            splitChunks: {
                minSize: 10000,
                maxSize: 250000,
            }
        }
    },
    // PWA disabled for Vue 3 migration - will re-enable after migration complete
    // pwa: {
    //     workboxPluginMode: 'InjectManifest',
    //     workboxOptions: {
    //         swSrc: './src/firebase-messaging-sw.js',
    //     }
    // }
}
