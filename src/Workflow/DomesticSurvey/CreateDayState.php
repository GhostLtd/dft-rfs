<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayMulti\ArrivedPortsType;
use App\Form\DomesticSurvey\DayMulti\ArrivedType;
use App\Form\DomesticSurvey\DayMulti\DepartedPortsType;
use App\Form\DomesticSurvey\DayMulti\DepartedType;
use App\Workflow\FormWizardInterface;

class CreateDayState implements FormWizardInterface
{
    const STATE_NUMBER_OF_STOPS = 'start';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_NUMBER_OF_STOPS => DepartedType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_NUMBER_OF_STOPS => 'domestic_survey/create-day/form-number-of-stops.html.twig',
    ];

    private $state = self::STATE_NUMBER_OF_STOPS;

    /** @var Day */
    private $subject;

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     * @return self
     */
    public function setState($state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === Day::class) throw new \InvalidArgumentException("Got " . get_class($subject) . ", expected " . DomesticStopDay::class);
        $this->subject = $subject;
        return $this;
    }

    public function isValidJumpInState($state)
    {
        return (in_array($state, $this->getValidJumpInStates()));
    }

    protected function getValidJumpInStates()
    {
        return [self::STATE_NUMBER_OF_STOPS];
    }

    public function getStateFormMap()
    {
        return self::FORM_MAP;
    }

    public function getStateTemplateMap()
    {
        return self::TEMPLATE_MAP;
    }

    public function getDefaultTemplate()
    {
        return null;
    }
}