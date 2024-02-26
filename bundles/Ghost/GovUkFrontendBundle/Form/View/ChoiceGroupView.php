<?php

namespace Ghost\GovUkFrontendBundle\Form\View;

use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView as SymfonyChoiceGroupView;

class ChoiceGroupView extends SymfonyChoiceGroupView
{
    public function __construct(SymfonyChoiceGroupView $original, public ?array $labelAttr = [], public string $labelHeadingElement = 'h1')
    {
        parent::__construct($original->label, $original->choices);
    }
}