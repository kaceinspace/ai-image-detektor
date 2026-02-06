<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

class NsfwJsService
{
    /**
     * Run NSFW.js classification on an image
     */
    public function analyze(string $imagePath): array
    {
        $startTime = microtime(true);

        // Path to node script
        $scriptPath = base_path('scripts/classify-image.cjs');
        
        // Command to run node script
        // Ensure 'node' is in PATH or specify absolute path to node executable if needed
        $process = new Process(['node', $scriptPath, $imagePath]);
        $process->setTimeout(60); // 1 minute timeout

        try {
            $process->mustRun();
            
            $output = $process->getOutput();
            
            // Extract JSON from output (NSFW.js might print logs/warnings)
            $jsonStart = strpos($output, '[');
            $jsonEnd = strrpos($output, ']');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
                $result = json_decode($jsonString, true);
            } else {
                $result = null; // Parse failed
            }
            
            if (!$result || json_last_error() !== JSON_ERROR_NONE) {
                Log::error('NSFW.js JSON Parse Error', ['output' => $output]);
                return [
                    'success' => false,
                    'error' => 'Invalid JSON output from NSFW.js'
                ];
            }
            
            if (isset($result['error'])) {
                Log::error('NSFW.js Execution Error', ['error' => $result['error']]);
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }

            // Map predictions to our Google Vision formatted structure
            return $this->mapPredictions($result, $startTime);

        } catch (ProcessFailedException $exception) {
            Log::error('NSFW.js Process Failed', ['message' => $exception->getMessage()]);
            return [
                'success' => false,
                'error' => 'Failed to run classification script'
            ];
        }
    }

    /**
     * Map NSFW.js probability to Google Vision Likelihood strings
     */
    private function mapPredictions(array $predictions, float $startTime): array
    {
        // Predictions is array of {className, probability}
        // Classes: 'Porn', 'Sexy', 'Hentai', 'Drawing', 'Neutral'
        
        $scores = [
            'Porn' => 0,
            'Sexy' => 0,
            'Hentai' => 0,
            'Drawing' => 0,
            'Neutral' => 0
        ];

        foreach ($predictions as $pred) {
            $scores[$pred['className']] = $pred['probability'];
        }

        // Mapping logic
        // Adult <-- Porn + Hentai
        $adultScore = max($scores['Porn'], $scores['Hentai']);
        
        // Racy <-- Sexy
        $racyScore = $scores['Sexy'];
        
        // Spoof <-- Drawing (Loose mapping, drawings could be considered spoof/fake in some contexts)
        $spoofScore = $scores['Drawing'];
        
        // Violence <-- Not supported by NSFW.js model
        $violenceScore = 0; 
        
        // Medical <-- Not supported
        $medicalScore = 0;

        return [
            'success' => true,
            'adult_likelihood' => $this->getLikelihoodString($adultScore),
            'violence_likelihood' => $this->getLikelihoodString($violenceScore),
            'racy_likelihood' => $this->getLikelihoodString($racyScore),
            'medical_likelihood' => $this->getLikelihoodString($medicalScore), // Not detected
            'spoof_likelihood' => $this->getLikelihoodString($spoofScore),
            'processing_time_ms' => (int)((microtime(true) - $startTime) * 1000),
            'raw_scores' => $scores
        ];
    }

    private function getLikelihoodString(float $probability): string
    {
        if ($probability >= 0.8) return 'VERY_LIKELY';
        if ($probability >= 0.6) return 'LIKELY';
        if ($probability >= 0.4) return 'POSSIBLE';
        if ($probability >= 0.2) return 'UNLIKELY';
        return 'VERY_UNLIKELY';
    }
}
