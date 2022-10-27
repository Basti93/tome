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
    pwa: {
        workboxPluginMode: 'InjectManifest',
        workboxOptions: {
            swSrc: './src/firebase-messaging-sw.js',
        }
    }
}
