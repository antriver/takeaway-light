const Client = require('castv2-client').Client;
const DefaultMediaReceiver = require('castv2-client').DefaultMediaReceiver;
const MDNS = require('mdns-js');

class GoogleHomeTalk {
    /**
     * Creates an instance of GoogleHomeTalk.
     * @param {string} [lang='en'] Language
     * @param {string} [accent='us'] Accent
     * @memberof GoogleHomeTalk
     */
    constructor(lang = 'en', accent = 'us') {
        this.lang = lang;
        this.accent = accent;
    }

    /**
     * Play a multimedia file with URL
     *
     * @param {string} deviceIp
     * @param {string} URL
     * @returns {Promise}
     * @memberof GoogleHomeTalk
     */
    playEndpoint(deviceIp, url) {
        return new Promise((resolve, reject) => {
            try {
                const client = new Client();
                client.connect(deviceIp, () => {
                    console.log(`Connecting to ${deviceIp} ...`);
                    client.launch(DefaultMediaReceiver, (err, Player) => {
                        if (err) {
                            reject(err);
                            // throw err;
                        }
                        let media = {
                            contentId: url,
                            contentType: 'audio/mp3',
                            streamType: 'BUFFERED'//'BUFFERED' // or LIVE
                        };
                        Player.load(media, { autoplay: true }, (err, status) => {
                            if (err) {
                                reject(err);
                                // throw err;
                            }
                            console.log(`[SUCCESS] Now playing ${url}`);
                            client.close();
                            resolve(status);
                        });
                    });
                });
                client.on('error', (error) => {
                    client.close();
                    // reject(error);
                    throw error;
                });
            } catch (error) {
                reject(error);
            }
        });
    }
}

module.exports = GoogleHomeTalk;
