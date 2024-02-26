<?php

namespace App\Tests\Form\Type\International\Action;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Form\CountryType;
use App\Form\InternationalSurvey\Action\PlaceType;
use App\Form\Validator\CanBeUnloadedValidator;
use App\Repository\International\ActionRepository;
use App\Tests\Form\Type\AbstractTypeTest;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlaceTypeTest extends AbstractTypeTest
{
    protected array $mockedReturnValue = [];
    protected ActionRepository&MockObject $actionRepositoryMock;

    #[\Override]
    protected function getExtensions(): array
    {
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
            ->willReturnCallback(fn(string $s) => $s);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension([
                    new CountryType($requestStackMock, $translatorMock),
                ], []),
            ]
        );
    }

    #[\Override]
    protected function getValidators(): array
    {
        $this->actionRepositoryMock = $this->createMock(ActionRepository::class);
        $this->actionRepositoryMock
            ->method('getLoadingActions')
            ->willReturnCallback(fn() => $this->mockedReturnValue);

        return [
            CanBeUnloadedValidator::class => new CanBeUnloadedValidator($this->actionRepositoryMock)
        ];
    }

    protected function dataPlaceWithNoExistingLoadingActions(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            // Since there are no existing loadings, we won't be allowed to unload and hence the loading => no
            // (unloading) option will be disabled...
            [['loading' => 'unload'], false],
            [['loading' => 'unload', 'place' => ['country' => ['country' => 'other', 'country_other' => 'England'], 'name' => 'Chichester']], false],

            // Loading => yes (loading) will work tho'...
            [['loading' => 'load'], false], // Missing place, country
            [['loading' => 'load', 'place' => ['country' => ['country' => 'GB']]], false], // Missing place
            [['loading' => 'load', 'place' => ['name' => 'Chichester']], false], // Missing country

            [['loading' => 'load', 'place' => ['country' => ['country' => 'GB'], 'name' => 'Chichester']], true],
            [['loading' => 'load', 'place' => ['country' => ['country' => 'other'], 'name' => 'Chichester']], false], // Missing country_other
            [['loading' => 'load', 'place' => ['country' => ['country' => 'other', 'country_other' => 'England'], 'name' => 'Chichester']], true],

            [['loading' => 'banana'], false],
        ];
    }

    /**
     * @dataProvider dataPlaceWithNoExistingLoadingActions
     */
    public function testPlaceWithNoExistingLoadingActions(array $formData, bool $expectedValid): void
    {
        $trip = (new Trip())
            ->setId('123');

        $data = (new Action())
            ->setTrip($trip);

        $this->performPlaceTypeTests($data, $formData, $expectedValid);
    }

    protected function dataPlaceWithExistingLoadingActions(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            // Since there are no existing loadings, we won't be allowed to unload and hence this option
            // will be disabled...
            [['loading' => 'unload'], false], // Missing place, country
            [['loading' => 'unload', 'place' => ['country' => ['country' => 'GB']]], false], // Missing place
            [['loading' => 'unload', 'place' => ['name' => 'Chichester']], false], // Missing country

            [['loading' => 'unload', 'place' => ['country' => ['country' => 'GB'], 'name' => 'Chichester']], true],
            [['loading' => 'unload', 'place' => ['country' => ['country' => 'other'], 'name' => 'Chichester']], false], // Missing country_other
            [['loading' => 'unload', 'place' => ['country' => ['country' => 'other', 'country_other' => 'England'], 'name' => 'Chichester']], true],

            [['loading' => 'load'], false], // Missing place, country
            [['loading' => 'load', 'place' => ['country' => ['country' => 'GB']]], false], // Missing place
            [['loading' => 'load', 'place' => ['name' => 'Chichester']], false], // Missing country

            [['loading' => 'load', 'place' => ['country' => ['country' => 'GB'], 'name' => 'Chichester']], true],
            [['loading' => 'load', 'place' => ['country' => ['country' => 'other'], 'name' => 'Chichester']], false], // Missing country_other
            [['loading' => 'load', 'place' => ['country' => ['country' => 'other', 'country_other' => 'England'], 'name' => 'Chichester']], true],

            [['loading' => 'banana'], false],
        ];
    }

    /**
     * @dataProvider dataPlaceWithExistingLoadingActions
     */
    public function testPlaceWithExistingLoadingActions(array $formData, bool $expectedValid): void
    {
        $trip = (new Trip())
            ->setId('123');

        $loadingAction = (new Action())
            ->setTrip($trip)
            ->setLoading(true);

        $trip->addAction($loadingAction);

        $this->mockedReturnValue = [$loadingAction];

        $data = (new Action())
            ->setTrip($trip);

        $this->performPlaceTypeTests($data, $formData, $expectedValid);
    }

    protected function dataPlaceWithExistingFullyUnloadedLoadingActions(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            // Nothing to unload because all loading actions have a matching unloading action that fully unloads them
            [['loading' => 'unload', 'place' => ['country' => ['country' => 'GB'], 'name' => 'Chichester']], false],
            [['loading' => 'unload', 'place' => ['country' => ['country' => 'other', 'country_other' => 'England'], 'name' => 'Chichester']], false],

            [['loading' => 'load'], false], // Missing place, country
            [['loading' => 'load', 'place' => ['country' => ['country' => 'GB']]], false], // Missing place
            [['loading' => 'load', 'place' => ['name' => 'Chichester']], false], // Missing country

            [['loading' => 'load', 'place' => ['country' => ['country' => 'GB'], 'name' => 'Chichester']], true],
            [['loading' => 'load', 'place' => ['country' => ['country' => 'other'], 'name' => 'Chichester']], false], // Missing country_other
            [['loading' => 'load', 'place' => ['country' => ['country' => 'other', 'country_other' => 'England'], 'name' => 'Chichester']], true],

            [['loading' => 'banana'], false],
        ];
    }


    /**
     * @dataProvider dataPlaceWithExistingFullyUnloadedLoadingActions
     */
    public function testPlaceWithExistingFullyUnloadedLoadingActions(array $formData, bool $expectedValid): void
    {
        $trip = (new Trip())
            ->setId('123');

        $loadingAction = (new Action())
            ->setTrip($trip)
            ->setLoading(true);

        $unloadingAction = (new Action())
            ->setTrip($trip)
            ->setLoading(false)
            ->setWeightUnloadedAll(true);

        $loadingAction->addUnloadingAction($unloadingAction);

        $trip
            ->addAction($loadingAction)
            ->addAction($unloadingAction);

        $this->mockedReturnValue = [$loadingAction];

        $data = (new Action())
            ->setTrip($trip);

        $this->performPlaceTypeTests($data, $formData, $expectedValid);
    }

    public function performPlaceTypeTests(Action $data, array $formData, bool $expectedValid): void
    {
        $form = $this->factory->create(PlaceType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $expectedCountry = $formData['place']['country']['country'] ?? null;

            if ($expectedCountry === 'other') {
                $expectedCountry = '0';
            }

            $expectedCountryOther = $formData['place']['country']['country_other'] ?? null;
            $expectedName = $formData['place']['name'] ?? null;

            $this->assertEquals(($formData['loading'] ?? null) === 'load', $data->getLoading());

            $this->assertEquals($expectedCountry, $data->getCountry());
            $this->assertEquals($expectedCountryOther, $data->getCountryOther());
            $this->assertEquals($expectedName, $data->getName());
        }
    }
}
