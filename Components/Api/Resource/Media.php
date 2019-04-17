<?php

namespace Port1Typo3Connector\Components\Api\Resource;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Thomas Griessbach <griessbach@portrino.de>, portrino GmbH
 */

use Shopware\Models\Media\Media as MediaModel;

/**
 * Class Media
 *
 * @package Port1Typo3Connector\Components\Api\Resource
 */
class Media extends \Shopware\Components\Api\Resource\Media
{

    /**
     * @param int $id
     *
     * @return array|MediaModel
     * @throws \Exception
     *
     */
    public function getOne($id)
    {
        $data = parent::getOne($id);

        $filters = [['property' => 'media.id', 'expression' => '=', 'value' => $id]];
        $query = $this->getRepository()->getMediaListQuery($filters, [], 1);

        /** @var MediaModel $mediaObject */
        $mediaObject = $query->getOneOrNullResult(self::HYDRATE_OBJECT);

        if ($mediaObject && $data) {
            $mediaService = Shopware()->Container()->get('shopware_media.media_service');

            if ($mediaObject->getType() === MediaModel::TYPE_IMAGE) {
                // get all thumbs
                $thumbnails = $mediaObject->getThumbnails();
                foreach ($thumbnails as $size => $path) {
                    // normalize paths
                    $thumbnails[$size] = $mediaService->getUrl($path);
                }
                $data['thumbnails'] = $thumbnails;
            }
        }

        return $data;
    }


    /**
     * @param int   $offset
     * @param int   $limit
     * @param array $criteria
     * @param array $orderBy
     *
     * @return array
     */
    public function getList($offset = 0, $limit = 25, array $criteria = [], array $orderBy = [])
    {
        $resultArray = parent::getList($offset, $limit, $criteria, $orderBy);
        $data = $resultArray['data'];

        $query = $this->getRepository()->getMediaListQuery($criteria, $orderBy, $limit, $offset);
        $query->setHydrationMode(self::HYDRATE_OBJECT);

        $paginator = $this->getManager()->createPaginator($query);

        // Returns the media data
        $mediaArray = $paginator->getIterator()->getArrayCopy();

        if ($resultArray['data'] && $mediaArray) {

            $mediaService = Shopware()->Container()->get('shopware_media.media_service');
            array_walk($resultArray['data'], function (&$item, $key) use ($mediaService, $mediaArray) {
                if ($item['type'] === MediaModel::TYPE_IMAGE) {

                    /** @var MediaModel $mediaObject */
                    $mediaObject = $mediaArray[$key];
                    if ($mediaObject && $mediaObject->getType() === MediaModel::TYPE_IMAGE) {
                        // get all thumbs
                        $thumbnails = $mediaObject->getThumbnails();
                        foreach ($thumbnails as $size => $path) {
                            // normalize paths
                            $thumbnails[$size] = $mediaService->getUrl($path);
                        }
                        $item['thumbnails'] = $thumbnails;

                    }
                }
            });
        }

        return ['data' => $resultArray['data'], 'total' => $resultArray['total']];
    }
}
