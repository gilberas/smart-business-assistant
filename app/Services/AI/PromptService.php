<?php

namespace App\Services\AI;

use App\Contracts\AI\PromptServiceInterface;
use App\Models\PromptTemplate;
use App\Repositories\AI\PromptTemplateRepository;
use App\Repositories\AI\PromptVersionRepository;

class PromptService implements PromptServiceInterface
{
    private array $variables = [];

    public function __construct(
        private readonly PromptTemplateRepository $templateRepository,
        private readonly PromptVersionRepository $versionRepository,
    ) {}

    public function render(int $templateId, array $variables = []): string
    {
        $template = $this->templateRepository->findById($templateId);
        if (!$template) {
            throw new \InvalidArgumentException("Prompt template #{$templateId} not found");
        }

        return $this->compile($template->content, $variables);
    }

    public function compile(string $template, array $variables): string
    {
        $result = $template;

        foreach ($variables as $key => $value) {
            $result = str_replace(
                ['{{' . $key . '}}', '{{ ' . $key . ' }}'],
                $this->formatValue($value),
                $result
            );
        }

        $unresolved = $this->findUnresolved($result);
        if (!empty($unresolved)) {
            foreach ($unresolved as $var) {
                $result = str_replace(['{{' . $var . '}}', '{{ ' . $var . ' }}'], '', $result);
            }
        }

        return $result;
    }

    public function validate(int $templateId, array $variables = []): array
    {
        $template = $this->templateRepository->findById($templateId);
        if (!$template) {
            return ['error' => "Prompt template #{$templateId} not found"];
        }

        $required = $this->getVariables($template->content);
        $missing = array_diff($required, array_keys($variables));
        $errors = [];

        if (!empty($missing)) {
            $errors['missing_variables'] = $missing;
        }

        $extra = array_diff(array_keys($variables), $required);
        if (!empty($extra)) {
            $errors['extra_variables'] = $extra;
        }

        return $errors;
    }

    public function getVariables(string $content): array
    {
        preg_match_all('/\{\{?\s*(\w+)\s*\}?\}/', $content, $matches);
        return array_unique($matches[1]);
    }

    public function createVersion(PromptTemplate $template, string $content, string $changelog = ''): void
    {
        $newVersion = $template->version + 1;

        $this->versionRepository->create([
            'prompt_template_id' => $template->id,
            'version' => $newVersion,
            'content' => $content,
            'changelog' => $changelog,
            'variables' => $this->getVariables($content),
            'created_by' => auth()->id(),
        ]);

        $template->update([
            'content' => $content,
            'version' => $newVersion,
            'variables' => $this->getVariables($content),
        ]);
    }

    public function preview(int $templateId, array $variables = []): string
    {
        $template = $this->templateRepository->findById($templateId);
        if (!$template) {
            return 'Template not found';
        }

        try {
            return $this->compile($template->content, $variables);
        } catch (\Throwable $e) {
            return "Error: {$e->getMessage()}";
        }
    }

    private function formatValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }

    private function findUnresolved(string $text): array
    {
        preg_match_all('/\{\{?\s*(\w+)\s*\}?\}/', $text, $matches);
        return array_unique($matches[1]);
    }
}
