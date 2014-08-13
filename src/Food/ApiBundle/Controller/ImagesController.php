<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ImagesController extends Controller
{
    public function imageAction(Request $request)
    {
        try {
            $filename = $request->get('imagename');
            $size = $request->get('size');
            $box = (bool)$request->get('box', false);
            $webPath = $this->get('kernel')->getRootDir() . '/../web/';
            $uploadService = $this->get('food.upload');

            if (!empty($filename)) {
                // So sesurity
                $filename = '/'.$filename;
            }

            if (!file_exists($webPath.$filename)) {
                throw new \InvalidArgumentException(
                    sprintf('Image "%s" could not be found. No resize will take place', $filename)
                );
            }

            // Jei jau turime sukarpyta - tikrinam ar jaunesnis nei savaites. Jei jaunas - atiduodam jau kirpta
            $mobileFile = $uploadService->getMobileImageName($filename, $size, $box);
            if (file_exists($webPath.$mobileFile)) {
                $lastWeek = strtotime('-1 week');

                if (filemtime($webPath.$mobileFile) > $lastWeek) {
                    $image = $mobileFile;
                }
            }

            // Jei visgi neturejom - kerpam kerpam
            if (!isset($image)) {
                $image = $uploadService->resizePhoto(
                    $filename,
                    $size,
                    $box
                );
            }

            $imagePath = $webPath.$image;

            $imageContents = file_get_contents($imagePath);
            $imageLength = filesize($imagePath);

            // Detect mime type
            $type = image_type_to_mime_type(exif_imagetype($imagePath));

            $response = new Response();

            $response->setContent($imageContents);
            $response->setStatusCode(Response::HTTP_OK);
            $response->headers->set('Content-Type', $type);
            $response->headers->set('Content-Length', $imageLength);

            return $response;
        } catch (\InvalidArgumentException $e) {
            $this->get('logger')->error($e->getMessage());

            return new Response('The image was not found in this dimension', 404);
        } catch (\Exception $e) {
            $this->get('logger')->error(
                sprintf(
                    'Resize can not continue. Error occured. Parameters: [imagename]: %s [size]: %s [box]: %s',
                    $request->get('imagename', ''),
                    $request->get('size', ''),
                    ($request->get('box', false) ? 'true' : 'false')
                )
            );

            return new Response('We are so sorry - an internal error occured. Please check parameters or grab a coffee', 500);
        }
    }
}
