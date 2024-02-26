<?php

namespace App\Tests\Form\Type\International\Action;

use App\Entity\International\Action;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Form\InternationalSurvey\Action\GoodsUnloadedWeightType;
use App\Repository\International\ActionRepository;
use App\Tests\Form\Type\AbstractTypeTest;
use App\Utility\CountryHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class GoodsUnloadedWeightTypeTest extends AbstractTypeTest
{
    protected ?Action $mockedReturnValue = null;
    protected ActionRepository&MockObject $actionRepositoryMock;

    #[\Override]
    protected function getExtensions(): array
    {
        $this->actionRepositoryMock = $this->createMock(ActionRepository::class);
        $this->actionRepositoryMock
            ->method('findOneByIdAndSurveyResponse')
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
            ->willReturnCallback(fn($s) => $s);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension([
                    new GoodsUnloadedWeightType(
                        $this->actionRepositoryMock,
                        new CountryHelper($requestStackMock),
                        $translatorMock,
                    ),
                ], []),
            ]
        );
    }

    protected function dataNotAlreadyPartiallyUnloaded(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['weightUnloadedAll' => 'yes'], true],
            [['weightUnloadedAll' => 'no'], false], // Need to fill the weightOfGoods field
            [['weightUnloadedAll' => 'banana'], false],

            [['weightUnloadedAll' => 'no', 'weightOfGoods' => '-1'], false], // No negative or decimal numbers
            [['weightUnloadedAll' => 'no', 'weightOfGoods' => '1.1'], false],

            // Equal to the loaded weight
            // (If we've said we've "not unloaded it all", then we need to specify a value that is LESS than the loaded weight)
            [['weightUnloadedAll' => 'no', 'weightOfGoods' => '10000'], false],

            [['weightUnloadedAll' => 'no', 'weightOfGoods' => '1'], true],
            [['weightUnloadedAll' => 'no', 'weightOfGoods' => '9999'], true],
        ];
    }

    /**
     * @dataProvider dataNotAlreadyPartiallyUnloaded
     */
    public function testNotAlreadyPartiallyUnloaded(array $formData, bool $expectedValid): void
    {
        ['loadingAction' => $loadingAction, 'unloadingAction' => $data] = $this->getLoadingActionAndUnloadingAction();
        $this->mockedReturnValue = $loadingAction;

        $form = $this->factory->create(GoodsUnloadedWeightType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals((($formData['weightUnloadedAll'] ?? null) === 'yes'), $data->getWeightUnloadedAll());
            $this->assertEquals($formData['weightOfGoods'] ?? null, $data->getWeightOfGoods());
        }
    }

    protected function dataAlreadyPartiallyUnloaded(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['weightUnloadedAll' => 'yes'], false], // Field doesn't exist when already partially unloaded
            [['weightUnloadedAll' => 'no'], false],
            [['weightUnloadedAll' => 'banana'], false],
            [['weightUnloadedAll' => 'no', 'weightOfGoods' => '1000'], false],

            [['weightOfGoods' => '-1'], false], // No negative or decimal numbers
            [['weightOfGoods' => '1.1'], false],

            // Since we've "not unloaded it all", then we need to specify a value that is LESS than the loaded weight

            // N.B. Validator does not perform calculations regarding how much remains on the truck.
            //      (e.g. if they load 10,000 and then unload 5,000 and unload 8,000 the system currently allows that)
            //
            // Rationale: better to get the data and question it afterwards rather than frustrate the user and
            //            not get the data at all.
            [['weightOfGoods' => '10000'], false],

            [['weightOfGoods' => '1'], true],
            [['weightOfGoods' => '9999'], true],
        ];
    }

    /**
     * @dataProvider dataAlreadyPartiallyUnloaded
     */
    public function testAlreadyPartiallyUnloaded(array $formData, bool $expectedValid): void
    {
        ['loadingAction' => $loadingAction, 'unloadingAction' => $data] = $this->getLoadingActionAndUnloadingAction();
        $this->mockedReturnValue = $loadingAction;

        // Add an extra unloading action that has already taken 2000 off
        $existingUnloadingAction = (new Action())
            ->setId('345')
            ->setCountry('BE')
            ->setName('Brussels')
            ->setLoadingAction($loadingAction)
            ->setTrip($loadingAction->getTrip())
            ->setLoading(false)
            ->setWeightOfGoods(2000)
            ->setWeightUnloadedAll(false);

        $loadingAction->addUnloadingAction($existingUnloadingAction);

        $form = $this->factory->create(GoodsUnloadedWeightType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());
        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals((($formData['weightUnloadedAll'] ?? null) === 'yes'), $data->getWeightUnloadedAll());
            $this->assertEquals($formData['weightOfGoods'] ?? null, $data->getWeightOfGoods());
        }
    }

    /**
     * @return array{loadingAction: Action, unloadingAction: Action}
     */
    public function getLoadingActionAndUnloadingAction(): array
    {
        $surveyResponse = new SurveyResponse();

        $vehicle = (new Vehicle())
            ->setSurveyResponse($surveyResponse);

        $trip = (new Trip())
            ->setId('987')
            ->setVehicle($vehicle);

        $loadingAction = (new Action())
            ->setId('123')
            ->setWeightOfGoods(10000)
            ->setCountry('GB')
            ->setName('Chichester')
            ->setTrip($trip)
            ->setLoading(true);

        $data = (new Action())
            ->setId('234')
            ->setCountry('FR')
            ->setName('Paris')
            ->setLoadingAction($loadingAction)
            ->setTrip($trip)
            ->setLoading(false);

        $loadingAction->addUnloadingAction($data);

        return [
            'loadingAction' => $loadingAction,
            'unloadingAction' => $data
        ];
    }
}
