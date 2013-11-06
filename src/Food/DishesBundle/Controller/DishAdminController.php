<?php
namespace Food\DishesBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sonata\AdminBundle\Exception\ModelManagerException;

class DishAdminController extends Controller
{

    /**
     * TODO omg hacking is here
     *
     * @param $object
     */
    public function deleteAction($id)
    {
        $logger = $this->get('logger');
        $logger->info('I just got the logger');

        $id     = $this->get('request')->get($this->admin->getIdParameter());
        /**
         * @var \Food\DishesBundle\Entity\Dish
         */
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if (false === $this->admin->isGranted('DELETE', $object)) {
            throw new AccessDeniedException();
        }

        if ($this->getRestMethod() == 'DELETE') {
            // check the csrf token
            $this->validateCsrfToken('sonata.delete');

            try {
                $object->setDeletedAt(new \DateTime());
                $object->setDeleted(1);
                $this->admin->update($object);

                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(array('result' => 'ok'));
                }

                $this->addFlash('sonata_flash_success', 'flash_delete_success');

            } catch (ModelManagerException $e) {

                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(array('result' => 'error'));
                }

                $this->addFlash('sonata_flash_error', 'flash_delete_error');
            }

            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        return $this->render($this->admin->getTemplate('delete'), array(
            'object'     => $object,
            'action'     => 'delete',
            'csrf_token' => $this->getCsrfToken('sonata.delete')
        ));
    }
}
