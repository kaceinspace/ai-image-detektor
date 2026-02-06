const fs = require('fs');
const path = require('path');

// Try loading native TFJS, fallback to pure JS
let tf;
let useNative = false;
try {
    tf = require('@tensorflow/tfjs-node');
    useNative = true;
} catch (e) {
    console.error("Native TFJS-Node not found/failed, using Pure JS fallback:", e.message);
    tf = require('@tensorflow/tfjs');
    require('@tensorflow/tfjs-backend-cpu'); // Ensure CPU backend is loaded
}

const nsfw = require('nsfwjs');

// Model path (local caching)
const MODEL_PATH = path.join(__dirname, '../storage/app/nsfw-model/');

// Ensure model directory exists
if (!fs.existsSync(MODEL_PATH)) {
    fs.mkdirSync(MODEL_PATH, { recursive: true });
}

// Use jpeg-js for pure JS decoding
let jpeg;
try {
    jpeg = require('jpeg-js');
} catch (e) { }

async function decodeImage(imagePath) {
    const buffer = fs.readFileSync(imagePath);

    if (useNative) {
        return tf.node.decodeImage(buffer, 3);
    } else {
        if (!jpeg) throw new Error("jpeg-js required for pure JS decoding but not installed");
        const pixels = jpeg.decode(buffer, { useTArray: true });
        // Create tensor [height, width, 4]
        const numChannels = 4;
        const tensor = tf.tensor3d(pixels.data, [pixels.height, pixels.width, numChannels], 'int32');
        // Slice to 3 channels (RGB)
        return tf.slice(tensor, [0, 0, 0], [-1, -1, 3]);
    }
}

async function run() {
    const imagePath = process.argv[2];

    if (!imagePath) {
        console.error(JSON.stringify({ error: 'No image path provided' }));
        process.exit(1);
    }

    try {
        // Check if model exists locally
        let model;
        // Construct model URL
        // Note: nsfwjs load expects file:// path to directory containing model.json
        const modelUrl = `file://${MODEL_PATH}/`;

        if (fs.existsSync(path.join(MODEL_PATH, 'model.json'))) {
            model = await nsfw.load(modelUrl, { size: 299 }); // mobilenet_v2 default
        } else {
            // Fallback: Load from web (and maybe save later? for now just load)
            // Ideally we should run download-model.js first
            model = await nsfw.load();
        }

        const image = await decodeImage(imagePath);
        const predictions = await model.classify(image);
        image.dispose();

        console.log(JSON.stringify(predictions));

    } catch (error) {
        console.error(JSON.stringify({ error: error.message }));
        process.exit(1);
    }
}

run();
