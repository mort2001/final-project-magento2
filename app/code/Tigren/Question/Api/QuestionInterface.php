<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Api;

interface QuestionInterface
{
    /**
     * @param int $id
     * @return bool|mixed
     */
    public function delete(int $id);

    /**
     * @return mixed
     */
    public function getQuestionData();

    /**
     * @param string $title
     * @param string $content
     * @return bool
     */
    public function save($title, $content);

    /**
     * @param int $id
     * @param string $title
     * @param string $content
     * @return bool
     */
    public function update($id, $title, $content);
}

