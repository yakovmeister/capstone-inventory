<?php

/**
 * (c) yakovmeister 2017
 * The utility functions
 * simple set of functions that code help us get going.
 * requires:
 * - php 7
 * - illuminate/database >= 5.4
 *----------------------------------------
 * @author Jacob Baring <electro7bug@gmail.com>
 */
use Capstone\Model\User;


/**
 * Attempts to authenticate user
 * @param  $username [login username]
 * @param  $password [unhashed/md5 password]
 * @return  mixed [boolean false if no user found, otherwise returns the user info]
 */
if(!function_exists("authenticate"))
{
    function authenticate($username, $password)
    {
        $authenticated = User::where("username", $username)->where("password", $password)->get();

        if(count($authenticated) == 1)
        {
            // get the first element 
            return $authenticated->first();
        }

        return false;
    }
}

/**
 * Checks whether a user is authenticated
 * @return bool [user id is set on session var]
 */
if(!function_exists("auth_check"))
{
    function auth_check()
    {
        return isset($_SESSION["uid"]);
    }
}

/**
 * Check whether the column has unique value
 * @param $model [instance of eloquent model]
 * @param $column [column]
 * @param $value [value]
 * @return bool [value is unique or not]
 */
if(!function_exists("check_unique"))
{
    function check_unique(\Illuminate\Database\Eloquent\Model $model, $column, $value)
    {
        $isUnique = $model->where($column, $value)->get();
        
        return count($isUnique) > 0 ? false : true;
    }
}

/**
 * Check whether the user's usertype is exact as the given parameter
 * @param $isWhat [usertype]
 * @return bool [user's usertype is equal to given usertype]
 */
if(!function_exists("userIs"))
{
    function userIs($isWhat)
    {
        return User::find($_SESSION['uid'])->usertype->title == $isWhat;
    }
}

/**
 * Dump and die
 * @param $dump [things you want to dump e.g. your trash girlfriend]
 */
if(!function_exists("dd"))
{
    function dd($dump)
    {
        die(var_dump($dump));
    }
}