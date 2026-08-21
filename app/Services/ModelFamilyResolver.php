<?php

namespace App\Services;

use App\Models\AiModel;
use Illuminate\Support\Str;

class ModelFamilyResolver
{
    /**
     * Resolve a human-friendly family name from the catalog model name.
     * Company is used separately by callers, so families with the same word
     * (for example Amazon Nova and Deepgram Nova) never cross companies.
     */
    public function name(AiModel|string $model): string
    {
        $name = trim($model instanceof AiModel ? $model->name : $model);

        $patterns = [
            '/^GPT(?:\s|\-|$)/i' => 'GPT',
            '/^o\d/i' => 'OpenAI o-series',
            '/^Claude(?:\s|$)/i' => 'Claude',
            '/^Gemini(?:\s|$)/i' => 'Gemini',
            '/^Gemma(?:\s|$)/i' => 'Gemma',
            '/^(?:Llama\s+)?Nemotron(?:\s|\-|$)/i' => 'Nemotron',
            '/^Llama(?:\s|$)/i' => 'Llama',
            '/^Grok(?:\s|$)/i' => 'Grok',
            '/^Mistral(?:\s|$)/i' => 'Mistral',
            '/^Ministral(?:\s|$)/i' => 'Ministral',
            '/^Codestral(?:\s|$)/i' => 'Codestral',
            '/^Pixtral(?:\s|$)/i' => 'Pixtral',
            '/^Devstral(?:\s|$)/i' => 'Devstral',
            '/^Command(?:\s|$)/i' => 'Command',
            '/^Aya(?:\s|$)/i' => 'Aya',
            '/^DeepSeek(?:\-|\s|$)/i' => 'DeepSeek',
            '/^Janus(?:\-|\s|$)/i' => 'Janus',
            '/^Qwen(?:\d|\s|\-|$)/i' => 'Qwen',
            '/^QwQ(?:\-|\s|$)/i' => 'QwQ',
            '/^Phi(?:\-|\s|$)/i' => 'Phi',
            '/^Jamba(?:\s|$)/i' => 'Jamba',
            '/^GLM(?:\-|\s|$)/i' => 'GLM',
            '/^CogVideo(?:\s|$)/i' => 'CogVideo',
            '/^Kimi(?:\s|$)/i' => 'Kimi',
            '/^Moonshot(?:\-|\s|$)/i' => 'Moonshot',
            '/^MiniMax(?:\-|\s|$)/i' => 'MiniMax',
            '/^Yi(?:\-|\s|$)/i' => 'Yi',
            '/^ERNIE(?:\s|$)/i' => 'ERNIE',
            '/^Hunyuan(?:\s|$)/i' => 'Hunyuan',
            '/^Pangu(?:\s|$)/i' => 'Pangu',
            '/^FLUX(?:\.|\s|$)/i' => 'FLUX',
            '/^Stable Diffusion(?:\s|$)/i' => 'Stable Diffusion',
            '/^Stable Image(?:\s|$)/i' => 'Stable Image',
            '/^Stable Audio(?:\s|$)/i' => 'Stable Audio',
            '/^Gen(?:\-|\s|$)/i' => 'Gen',
            '/^Ray(?:\d|\s|\-|$)/i' => 'Ray',
            '/^Pika(?:\s|$)/i' => 'Pika',
            '/^Eleven(?:\s|$)/i' => 'Eleven',
            '/^Multilingual(?:\s|$)/i' => 'Eleven Multilingual',
            '/^Flash(?:\s|$)/i' => 'Eleven Flash',
            '/^Amazon Nova(?:\s|$)/i' => 'Amazon Nova',
            '/^Nova(?:\-|\s|$)/i' => 'Nova',
            '/^Aura(?:\-|\s|$)/i' => 'Aura',
            '/^Universal(?:\-|\s|$)/i' => 'Universal',
            '/^Sonic(?:\-|\s|$)/i' => 'Sonic',
            '/^EVI(?:\s|$)/i' => 'EVI',
            '/^AlphaFold(?:\s|$)/i' => 'AlphaFold',
            '/^ESM(?:\d|\s|\-|$)/i' => 'ESM',
            '/^Cosmos(?:\s|$)/i' => 'Cosmos',
            '/^MAI(?:\-|\s|$)/i' => 'MAI',
        ];

        foreach ($patterns as $pattern => $family) {
            if (preg_match($pattern, $name)) {
                return $family;
            }
        }

        // Safe generic fallback: strip obvious trailing versions/sizes while
        // preserving a recognizable family label.
        $family = preg_replace('/\s+(?:v?\d+(?:\.\d+)*(?:[A-Za-z-]+)?|\d+(?:\.\d+)?[BMK])(?:\s.*)?$/i', '', $name);
        $family = trim((string) $family);

        return $family !== '' ? Str::limit($family, 80, '') : $name;
    }

    public function key(AiModel $model): string
    {
        return (string) $model->company_id.'|'.Str::lower($this->name($model));
    }
}
