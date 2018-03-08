<?php

/**
 * Created by PhpStorm.
 * User: lihaibo
 * Date: 2016/3/26
 * Time: 20:23
 */
namespace Service\Model;

use Think\Model;

class UserModel extends Model {

    public function getAll() {
        return $this->select();
    }
}