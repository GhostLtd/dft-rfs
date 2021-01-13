<?php


namespace App\Controller\Admin\Domestic;

use App\Utility\DvlaImporter;
use App\Utility\RegistrationMarkHelper;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/{type}/surveys/import", name="admin_domestic_importdvla_", requirements={"type": "gb|ni"})
 */
class ImportFromDvlaController extends AbstractController
{
    const SESSION_KEY = 'domestic-survey-import-data';

    /**
     * @Route("", name="index")
     * @Template()
     */
    public function index(Request $request, Session $session)
    {
        $form = $this->createFormBuilder()
            ->add('file', Gds\FileUploadType::class)
            ->add('submit', Gds\ButtonType::class, ['type' => 'submit'])
            ->getForm()
            ;
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $dvlaImport = new DvlaImporter();
            $session->set(self::SESSION_KEY, $dvlaImport->getDataFromFile($form->get('file')->getData()));
            return $this->redirectToRoute('admin_domestic_importdvla_moderate', $request->attributes->all());
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/moderate", name="moderate")
     * @Template("admin/domestic/import_from_dvla/index.html.twig")
     */
    public function moderateResults(Session $session)
    {
        // grab the data from the session
        $data = $session->get(self::SESSION_KEY, []);

        // create a form
        $formBuilder = $this->createFormBuilder(array_fill_keys(array_keys($data), true));
        $formBuilder->add('imported_data', Gds\ChoiceType::class, [
            'inherit_data' => true,
            'expanded' => true,
            'multiple' => true,
            'choice_loader' => new CallbackChoiceLoader(function() use ($data) {
                return array_combine(array_map('serialize', $data), array_keys($data));
            }),
            'choice_label' => function($choice, $key, $value) {
                $data = unserialize($key);
                $regMark = new RegistrationMarkHelper($data[DvlaImporter::COL_REG_MARK]);
                $address1 = ucwords(strtolower($data[DvlaImporter::COL_ADDRESS_1]));
                return "{$regMark->getFormattedRegistrationMark()} - {$address1} / {$data[DvlaImporter::COL_POSTCODE]}";
            },
        ]);
        $form = $formBuilder->getForm();

        // show it
        return [
            'form' => $form->createView(),
        ];
    }
}