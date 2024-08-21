<?php

namespace App\Tests\Form\Type\International\Action;

use App\Entity\International\Action;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Form\InternationalSurvey\Action\LoadingPlaceType;
use App\Repository\International\ActionRepository;
use App\Tests\Form\Type\AbstractTypeTest;
use App\Utility\CountryHelper;
use App\Utility\LoadingPlaceHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoadingPlaceTypeTest extends AbstractTypeTest
{
    protected array $mockedReturnValue = [];
    protected ActionRepository&MockObject $actionRepositoryMock;

    #[\Override]
    protected function getExtensions(): array
    {
        $this->actionRepositoryMock = $this->createMock(ActionRepository::class);
        $this->actionRepositoryMock
            ->method('getLoadingActions')
            ->willReturnCallback(fn() => $this->mockedReturnValue);

        $requestMock = $this->createMock(Request::class);
        $requestMock
            ->method('getLocale')
            ->willReturn('en');

        $requestStackMock = $this->createMock(RequestStack::class);
        $requestStackMock
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock
            ->method('trans')
            ->willReturnCallback(fn(string $s, array $p = []) => match($s) {
                'international.action.stop' => "{$p['place']} {$p['country']} {$p['number']} {$p['goods']}",
                default => $s,
            });

        $loadingPlaceHelper = new LoadingPlaceHelper(
            $this->actionRepositoryMock,
            new CountryHelper($requestStackMock),
            $translatorMock
        );

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension([
                    new LoadingPlaceType($loadingPlaceHelper),
                ], []),
            ]
        );
    }

    protected function dataLoadingPlace(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['loadingAction' => 'action-1'], true, '123'],
            [['loadingAction' => 'action-2'], true, '234'],

            [['loadingAction' => 'action-3'], false],
            [['loadingAction' => 'banana'], false],
        ];
    }

    /**
     * @dataProvider dataLoadingPlace
     */
    public function testLoadingPlace(array $formData, bool $expectedValid, ?string $expectedLoadingActionId=null): void
    {
        $surveyResponse = new SurveyResponse();

        $vehicle = (new Vehicle())
            ->setSurveyResponse($surveyResponse);

        $trip = (new Trip())
            ->setId('987')
            ->setVehicle($vehicle);

        $loadingActions = [
            (new Action())
                ->setId('123')
                ->setNumber(1)
                ->setWeightOfGoods(10000)
                ->setCountry('GB')
                ->setName('Chichester')
                ->setTrip($trip)
                ->setGoodsDescription('packaging')
                ->setLoading(true),
            (new Action())
                ->setId('234')
                ->setNumber(2)
                ->setWeightOfGoods(20000)
                ->setCountry('GB')
                ->setName('Felpham')
                ->setTrip($trip)
                ->setGoodsDescription('other-goods')
                ->setLoading(true),
        ];

        $this->mockedReturnValue = $loadingActions;

        $data = (new Action())
            ->setTrip($trip);

        $form = $this->factory->create(LoadingPlaceType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($expectedLoadingActionId, $data->getLoadingAction()?->getId());
        }
    }
}
