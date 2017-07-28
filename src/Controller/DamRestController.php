<?php


namespace Drupal\dam_rest\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for DAM REST routes.
 */
class DamRestController extends ControllerBase implements ContainerInjectionInterface {

  public function uploadFile() {
    /** @var Request $object */
    $request = \Drupal::getContainer()->get('request_stack')->getCurrentRequest();

    $requestContent = json_decode($request->getContent(), FALSE);

    $fileContent = base64_decode($requestContent->content);
    $fileEntity = $requestContent->entity;

    \Drupal::logger('dam')->notice('Received ' . var_export($fileEntity, TRUE));

    $file = file_save_data($fileContent, 'public://' . date('Y-m') . '/' . $fileEntity->filename);
//    print_r($file);
    if (!$file) {
      return new Response(json_encode($this->t('Could not create file :name.', array(':name' => $fileEntity->filename))), 500);
    }

    $file->setFilename($fileEntity->filename);
//    $file->filemime = $fileEntity->filemime;
//    $file->alt = $fileEntity->alt;
//    $file->title = $fileEntity->title;
//    $file->status = FILE_STATUS_PERMANENT;
    $file->save();

    return new Response(json_encode($this->t(':uri', array(':uri' => $file->getFileUri()))));
  }

  public function searchFile() {
    $results = views_get_view_result('files', 'page_1', '', 'image');
    $images = [];
    foreach ($results as $item) {
      $entity = $item->_entity;
      $images[$entity->uuid()] = (object)[
        'uuid' => $entity->uuid(),
        'uri' => $entity->getFileUri(),
        'filemime' => $entity->getMimeType(),
        'filesize' => $entity->getSize(),
      ];
    }

    return new Response(json_encode($images));
  }

}