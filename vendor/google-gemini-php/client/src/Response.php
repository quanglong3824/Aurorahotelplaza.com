<?php
/**
 * Gemini Response - Standalone Version
 * 
 * Xử lý phản hồi từ Gemini API
 */

namespace Gemini;

class Response
{
    private array $rawResponse;
    private string $text = '';

    public function __construct(array $rawResponse)
    {
        $this->rawResponse = $rawResponse;
        $this->extractText();
    }

    /**
     * Get the full text response
     */
    public function text(): string
    {
        return $this->text;
    }

    /**
     * Get raw response array
     */
    public function raw(): array
    {
        return $this->rawResponse;
    }

    /**
     * Extract text from response
     */
    private function extractText(): void
    {
        // Gemini API response structure:
        // {
        //   "candidates": [
        //     {
        //       "content": {
        //         "parts": [
        //           { "text": "..." }
        //         ]
        //       }
        //     }
        //   ]
        // }

        if (isset($this->rawResponse['candidates']) && is_array($this->rawResponse['candidates'])) {
            foreach ($this->rawResponse['candidates'] as $candidate) {
                if (isset($candidate['content']['parts'])) {
                    foreach ($candidate['content']['parts'] as $part) {
                        if (isset($part['text'])) {
                            $this->text .= $part['text'];
                        }
                    }
                }
            }
        }

        // Alternative structure for streaming responses
        if (empty($this->text) && isset($this->rawResponse['text'])) {
            $this->text = $this->rawResponse['text'];
        }

        // Handle error responses
        if (empty($this->text) && isset($this->rawResponse['error'])) {
            $error = $this->rawResponse['error'];
            $errorMsg = isset($error['message']) ? $error['message'] : 'Unknown error';
            throw new \Exception("Gemini API Error: $errorMsg");
        }
    }

    /**
     * Check if response has error
     */
    public function hasError(): bool
    {
        return isset($this->rawResponse['error']);
    }

    /**
     * Get error message if any
     */
    public function getError(): ?string
    {
        if (!$this->hasError()) {
            return null;
        }
        return $this->rawResponse['error']['message'] ?? 'Unknown error';
    }
}