<?php

declare(strict_types=1);

namespace Indykoning\GrumPHPPrettier;

use GrumPHP\Collection\ProcessArgumentsCollection;
use GrumPHP\Fixer\Provider\FixableProcessResultProvider;
use GrumPHP\Formatter\ProcessFormatterInterface;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Config\ConfigOptionsResolver;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

/**
 * Prettier task.
 *
 * @extends AbstractExternalTask<ProcessFormatterInterface>
 */
class PrettierTask extends AbstractExternalTask
{
    public function getName(): string
    {
        return 'prettier';
    }

    public static function getConfigurableOptions(): ConfigOptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'command' => getcwd() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['node_modules', '.bin', 'prettier']),
            'triggered_by' => [
                'js', 'ts', 'jsx', 'tsx', 'vue', // JS-Like files
                'css', 'less', 'scss', 'sass', // CSS-Like files
                'html', 'blade.php', 'antlers', 'phtml', // Template-Like files
            ],
            'ignore_patterns' => [],
            'ignore_unknown' => true,

            'arrow_parens' => null,
            'bracket_same_line' => null,
            'no_bracket_spacing' => null,
            'end_of_line' => null,
            'html_whitespace_sensitivity' => null,
            'parser' => null,
            'print_width' => null,
            'prose_wrap' => null,
            'quote_props' => null,
            'no_semi' => null,
            'single_attribute_per_line' => null,
            'single_quote' => null,
            'tab_width' => null,
            'trailing_comma' => null,
            'use_tabs' => null,
            'vue_indent_script_and_style' => null,
        ]);

        $resolver->addAllowedTypes('command', ['string']);
        $resolver->addAllowedTypes('triggered_by', ['array']);
        $resolver->addAllowedTypes('ignore_patterns', ['array']);

        $resolver->addAllowedTypes('ignore_unknown', ['boolean']);
        $resolver->addAllowedTypes('arrow_parens', ['null', 'string']);
        $resolver->addAllowedTypes('bracket_same_line', ['null', 'boolean']);
        $resolver->addAllowedTypes('no_bracket_spacing', ['null', 'boolean']);
        $resolver->addAllowedTypes('end_of_line', ['null', 'string']);
        $resolver->addAllowedTypes('html_whitespace_sensitivity', ['null', 'string']);
        $resolver->addAllowedTypes('parser', ['null', 'string']);
        $resolver->addAllowedTypes('print_width', ['null', 'int']);
        $resolver->addAllowedTypes('prose_wrap', ['null', 'string']);
        $resolver->addAllowedTypes('quote_props', ['null', 'string']);
        $resolver->addAllowedTypes('no_semi', ['null', 'boolean']);
        $resolver->addAllowedTypes('single_attribute_per_line', ['null', 'boolean']);
        $resolver->addAllowedTypes('single_quote', ['null', 'boolean']);
        $resolver->addAllowedTypes('tab_width', ['null', 'int']);
        $resolver->addAllowedTypes('trailing_comma', ['null', 'string']);
        $resolver->addAllowedTypes('use_tabs', ['null', 'boolean']);
        $resolver->addAllowedTypes('vue_indent_script_and_style', ['null', 'boolean']);

        return ConfigOptionsResolver::fromClosure(
            static fn (array $options): array => $resolver->resolve($options)
        );
    }

    public function canRunInContext(ContextInterface $context): bool
    {
        return $context instanceof GitPreCommitContext || $context instanceof RunContext;
    }

    public function run(ContextInterface $context): TaskResultInterface
    {
        $config = $this->getConfig()->getOptions();
        $files = $context->getFiles()->extensions($config['triggered_by']);
        foreach ($config['ignore_patterns'] as $pattern) {
            $files = $files->notPath($pattern);
        }

        if (0 === \count($files)) {
            return TaskResult::createSkipped($this, $context);
        }

        $arguments = ProcessArgumentsCollection::forExecutable((string) realpath($config['command']));

        $arguments->addOptionalArgument('--ignore-unknown', $config['ignore_unknown']);
        $arguments->addOptionalArgument('--arrow-parens=%s', $config['arrow_parens']);
        $arguments->addOptionalArgument('--bracket-same-line', $config['bracket_same_line']);
        $arguments->addOptionalArgument('--no-bracket-spacing', $config['no_bracket_spacing']);
        $arguments->addOptionalArgument('--end-of-line=%s', $config['end_of_line']);
        $arguments->addOptionalArgument('--html-whitespace-sensitivity=%s', $config['html_whitespace_sensitivity']);
        $arguments->addOptionalArgument('--parser=%s', $config['parser']);
        $arguments->addOptionalArgument('--print-width=%s', $config['print_width']);
        $arguments->addOptionalArgument('--prose-wrap=%s', $config['prose_wrap']);
        $arguments->addOptionalArgument('--quote-props=%s', $config['quote_props']);
        $arguments->addOptionalArgument('--no-semi', $config['no_semi']);
        $arguments->addOptionalArgument('--single-attribute-per-line', $config['single_attribute_per_line']);
        $arguments->addOptionalArgument('--single-quote', $config['single_quote']);
        $arguments->addOptionalArgument('--tab-width=%s', $config['tab_width']);
        $arguments->addOptionalArgument('--trailing-comma=%s', $config['trailing_comma']);
        $arguments->addOptionalArgument('--use-tabs', $config['use_tabs']);
        $arguments->addOptionalArgument('--vue-indent-script-and-style', $config['vue_indent_script_and_style']);
        $arguments->add('--check');

        $arguments->addFiles($files);
        $process = $this->processBuilder->buildProcess($arguments);

        $process->run();

        if (!$process->isSuccessful()) {
            return FixableProcessResultProvider::provide(
                TaskResult::createFailed($this, $context, $this->formatter->format($process)),
                function () use ($arguments): Process {
                    $arguments->removeElement('--check');
                    $arguments->add('--write');

                    return $this->processBuilder->buildProcess($arguments);
                }
            );
        }

        return TaskResult::createPassed($this, $context);
    }
}
