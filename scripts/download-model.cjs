const nsfw = require('nsfwjs');
const fs = require('fs');
const path = require('path');
// We require tfjs-node for file saving usually, or we can just use nsfwjs functionality if it exposes saving?
// Actually nsfwjs doesn't have a direct "save" method for the model object easily exposed in docs, 
// usually one loads it and then uses model.model.save().
// Try loading native TFJS, fallback to pure JS
let tf;
try {
    tf = require('@tensorflow/tfjs-node');
} catch (e) {
    console.log("Native TFJS-Node not found, using Pure JS fallback");
    tf = require('@tensorflow/tfjs');
    require('@tensorflow/tfjs-backend-cpu');
}

const MODEL_PATH = path.join(__dirname, '../storage/app/nsfw-model');

if (!fs.existsSync(MODEL_PATH)) {
    fs.mkdirSync(MODEL_PATH, { recursive: true });
}

async function download() {
    console.log("Downloading NSFW.js model...");
    try {
        const model = await nsfw.load(); // Load from default URL
        // The underlying model is a tf.GraphModel or similar
        // We can access it via model.model (it's the property name in nsfwjs source)

        console.log("Model loaded. Saving to:", MODEL_PATH);
        await model.model.save(`file://${MODEL_PATH}`);
        console.log("Model saved successfully!");

    } catch (error) {
        console.error("Error downloading model:", error);
    }
}

download();
