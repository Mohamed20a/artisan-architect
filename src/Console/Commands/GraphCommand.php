<?php

namespace ArtisanArchitect\Console\Commands;

use ArtisanArchitect\Analysis\ProjectAnalyzer;
use Illuminate\Console\Command;

class GraphCommand extends Command
{
    protected $signature = 'architect:graph 
                            {--format=html : Output format (html, json, mermaid)}
                            {--output=architecture.html : Output file path}';

    protected $description = 'Generate an architecture graph for the Laravel application';

    public function handle(ProjectAnalyzer $projectAnalyzer): int
    {
        $this->info('Analyzing architecture to build graph...');
        $report = $projectAnalyzer->analyze(base_path());

        $nodes = [];
        $edges = [];

        foreach ($report->results as $result) {
            $class = $result->class;
            $type = $result->type;
            
            $nodes[$class] = [
                'id' => $class,
                'label' => $class,
                'type' => $type,
            ];

            // Add dependencies
            if (!empty($result->data['dependencies'])) {
                foreach ($result->data['dependencies'] as $dep) {
                    // تجاهل التبعيات الذاتية
                    if ($dep === $class) {
                        continue;
                    }
                    
                    $edges[] = [
                        'from' => $class,
                        'to' => $dep,
                        'type' => 'dependency',
                        'label' => 'uses',
                    ];
                }
            }
        }

        $format = $this->option('format');
        $outputFile = $this->option('output');

        if ($format === 'json') {
            $content = json_encode(['nodes' => array_values($nodes), 'edges' => $edges], JSON_PRETTY_PRINT);
        } elseif ($format === 'mermaid') {
            $content = $this->generateMermaid($nodes, $edges);
        } else {
            // Default to HTML
            $mermaid = $this->generateMermaid($nodes, $edges);
            $content = $this->generateHtml($mermaid);
        }

        $path = $this->getGraphPath((string)$format, (string)$outputFile);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        $this->info("Graph exported successfully to {$path}!");

        return self::SUCCESS;
    }

    private function generateMermaid(array $nodes, array $edges): string
    {
        $lines = ["classDiagram"];
        
        // تجميع العقد الفريدة
        $uniqueNodes = [];
        foreach ($nodes as $node) {
            $id = $this->sanitizeMermaidId($node['id']);
            $uniqueNodes[$id] = $node;
        }
        
        // إضافة العقد إلى الرسم البياني
        foreach ($uniqueNodes as $id => $node) {
            $type = $this->sanitizeMermaidText($node['type'] ?? 'class');
            $lines[] = "    class {$id} {";
            $lines[] = "        <<{$type}>>";
            $lines[] = "    }";
        }

        // إضافة العلاقات
        $uniqueEdges = [];
        foreach ($edges as $edge) {
            $from = $this->sanitizeMermaidId($edge['from']);
            $to = $this->sanitizeMermaidId($edge['to']);
            $label = $this->sanitizeMermaidText($edge['label'] ?? 'uses');
            
            // تجنب العلاقات المكررة
            $key = $from . '->' . $to;
            if (!isset($uniqueEdges[$key])) {
                $uniqueEdges[$key] = "    {$from} ..> {$to} : {$label}";
            }
        }

        $lines = array_merge($lines, array_values($uniqueEdges));

        return implode("\n", $lines);
    }

    /**
     * تنظيف المعرف ليكون صالحاً في Mermaid
     */
    private function sanitizeMermaidId(string $name): string
    {
        // استخراج اسم الكلاس فقط (بدون namespace)
        if (str_contains($name, '\\')) {
            $parts = explode('\\', $name);
            $name = end($parts);
        }
        
        // إزالة أي أحرف غير مسموح بها في Mermaid
        $cleaned = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        
        // إذا أصبح فارغاً، استخدم اسم افتراضي
        if (empty($cleaned)) {
            $cleaned = 'Class_' . md5($name);
        }
        
        return $cleaned;
    }

    /**
     * تنظيف النص ليكون صالحاً في Mermaid (ترميز الأحرف الخاصة)
     */
    private function sanitizeMermaidText(string $text): string
    {
        // ترميز الأحرف الخاصة التي تسبب أخطاء في Mermaid
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // إزالة أي أحرف غير مرغوب فيها
        $text = preg_replace('/[^\w\s\-:\.]/', '', $text);
        
        return $text;
    }

    private function generateHtml(string $mermaidCode): string
    {
        // ترميز الكود ليكون آمناً في HTML
        $escapedMermaid = htmlspecialchars($mermaidCode, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Architecture Graph</title>
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        mermaid.initialize({ 
            startOnLoad: true, 
            theme: 'dark',
            securityLevel: 'loose'
        });
    </script>
    <style>
        body { 
            background-color: #0f172a; 
            display: flex; 
            justify-content: center; 
            align-items: center;
            padding: 2rem; 
            margin: 0; 
            min-height: 100vh;
        }
        .mermaid {
            background: #1e293b;
            padding: 2rem;
            border-radius: 1rem;
            width: 100%;
            max-width: 1400px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <pre class="mermaid">
{$escapedMermaid}
    </pre>
</body>
</html>
HTML;
    }

    protected function getGraphPath(string $format, string $outputFile): string
    {
        // If the user specified a custom name other than the default
        if ($outputFile !== 'architecture.html') {
            $filename = $outputFile;
        } else {
            $filename = match($format) {
                'json' => 'architecture.json',
                'mermaid' => 'architecture.mermaid',
                'svg' => 'architecture.svg.html',
                default => 'architecture.html',
            };
        }

        if ($this->isExplicitPath($filename)) {
            return $filename;
        }

        $baseDir = config('artisan-architect.output.directory', 'artisan-architect');
        if (!$this->isExplicitPath((string)$baseDir)) {
            $baseDir = base_path((string)$baseDir);
        }

        return rtrim((string)$baseDir, '\\/') 
            . DIRECTORY_SEPARATOR 
            . trim(config('artisan-architect.output.graphs_directory', 'graphs'), '\\/') 
            . DIRECTORY_SEPARATOR 
            . $filename;
    }

    protected function isExplicitPath(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Z]:/i', $path) === 1;
    }
}