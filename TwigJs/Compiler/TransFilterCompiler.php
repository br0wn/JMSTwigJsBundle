<?php

namespace JMS\TwigJsBundle\TwigJs\Compiler;

use Symfony\Component\Translation\TranslatorInterface;
use TwigJs\JsCompiler;
use TwigJs\FilterCompilerInterface;

class TransFilterCompiler implements FilterCompilerInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getName()
    {
        return 'trans';
    }

    public function compile(JsCompiler $compiler, \Twig_Node_Expression_Filter $node)
    {
        if (!($locale = $compiler->getDefine('locale'))) {
            return false;
        }

        // ignore dynamic messages, we cannot resolve these
        // users can still apply a runtime trans filter to do this
        $subNode = $node->getNode('node');
        if (!$subNode instanceof \Twig_Node_Expression_Constant) {
            return false;
        }

        $id = $subNode->getAttribute('value');
        $domain = 'messages';
        $hasParams = false;

        $arguments = $node->getNode('arguments');
        if (count($arguments) > 0) {
            $hasParams = count($arguments->getNode(0)) > 0;

            if ($arguments->hasNode(1)) {
                $domainNode = $arguments->getNode(1);

                if (!$domainNode instanceof \Twig_Node_Expression_Constant) {
                    return false;
                }

                $domain = $domainNode->getAttribute('value');
            }
        }

        $translated = $this->translator->trans($id, [], $domain, $locale);

        if (!$hasParams) {
            $compiler->string($translated);

            return;
        }

        $compiler
            ->raw('twig.filter.replace(')
            ->string($translated)
            ->raw(", ")
            ->subcompile($arguments->getNode(0))
            ->raw(')')
        ;
    }
}
