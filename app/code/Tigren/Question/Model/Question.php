<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Model;

use Exception;
use Tigren\Question\Api\QuestionInterface;
use Magento\Customer\Model\Session;

/**
 * Class Question
 * @package Tigren\Question\Model
 */
class Question implements QuestionInterface
{
    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var PostFactory
     */
    protected $_postFactory;

    /**
     * @var array
     */
    protected array $response = [];

    /**
     * @param PostFactory $postFactory
     * @param Session $session
     */
    public function __construct(PostFactory $postFactory, Session $session)
    {
        $this->_session = $session;
        $this->_postFactory = $postFactory;
    }

    /**
     * @return array
     */
    public function getQuestionData()
    {
        return $this->_postFactory->create()->getCollection()->getData();
    }

    /**
     * @param int $id
     * @return bool|mixed
     */
    public function delete(int $id)
    {
        $result = [];
        try {
            if ($id) {
                $post = $this->_postFactory->create()->load($id);
                $post->delete();
                $result['delete'] = true;
            } else {
                $result['delete'] = false;
            }
        } catch (Exception $e) {
        }
        return $result['delete'];
    }

    /**
     * @param string $title
     * @param string $content
     * @return bool
     * @throws Exception
     */
    public function save($title, $content)
    {
        $result = [];
        if (isset($title, $content)) {
            $post = $this->_postFactory->create();
            $authorId = $this->_session->getCustomerId();

            $arr = [
                'title' => $title,
                'content' => $content,
                'author_id' => $authorId
            ];
            $post->addData($arr);
            $post->save();

            $result['save'] = true;
        } else {
            $result['save'] = false;
        }
        return $result['save'];
    }

    /**
     * @param int $id
     * @param string $title
     * @param string $content
     * @return bool
     * @throws Exception
     */
    public function update($id, $title, $content)
    {
        $result = [];
        if (isset($title, $content, $id)) {
            $post = $this->_postFactory->create()->load($id);
            $arr = [
                'title' => $title,
                'content' => $content,
            ];
            $post->addData($arr);
            $post->save();
            $result['update'] = true;
        } else {
            $result['update'] = false;
        }
        return $result['update'];
    }
}