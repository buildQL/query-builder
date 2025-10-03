<?php

use BuildQL\Database\Query\Builder;
use BuildQL\Database\Query\DB;
use BuildQL\Database\Query\Exception\BuilderException;

beforeEach(function (){
    DB::resetConnection();
    DB::setConnection(
        "localhost", "root", "", "foreign"
    );
});

describe("Builder class testing", function (){

    test("column aliases and parent test", function (){
        expect(
            DB::table("profiles:pr")->toRawSql()
        )->toContain("SELECT * FROM `profiles` as `pr`");

        expect(
            DB::table("users")->select(['count(*):total_users'])->toRawSql()
        )->toContain("SELECT count(*) as `total_users` FROM `users`");

        expect(
            DB::table("profiles")->select(['min(age):min_user_age', 'max(age):max_user_age'])->toRawSql()
        )->toContain("SELECT min(`age`) as `min_user_age`, max(`age`) as `max_user_age` FROM `profiles`");

        // by using selectAggregate() method
        expect(
            DB::table("profiles")
            ->select([])
            ->selectAggregate(min: 'age:min_user_age', max: 'age:max_user_age')->toRawSql()
        )->toContain("SELECT min(`age`) as `min_user_age`, max(`age`) as `max_user_age` FROM `profiles`");
    });

    test("builder fluent methods return value test", function (){
        $db = DB::table("users");

        expect($db::class)->toEqual(Builder::class);
        expect($db->join("profiles", "users.id", "profiles.user_id")::class)->toEqual(Builder::class);
        expect($db->leftJoin("profiles", "users.id", "profiles.user_id")::class)->toEqual(Builder::class);
        expect($db->rightJoin("profiles", "users.id", "profiles.user_id")::class)->toEqual(Builder::class);
        expect($db->crossJoin("profiles")::class)->toEqual(Builder::class);
        expect($db->groupBy("name")::class)->toEqual(Builder::class);
        expect($db->selectAggregate()::class)->toEqual(Builder::class);
        expect($db->select(["*"])::class)->toEqual(Builder::class);
        expect($db->distinct()::class)->toEqual(Builder::class);
        expect($db->where("name", "Taylor Otwell")::class)->toEqual(Builder::class);
        expect($db->orWhere("name", "Taylor Otwell")::class)->toEqual(Builder::class);
        expect($db->whereIn("name", ["Taylor Otwell", "Rasmus Lerdouf"])::class)->toEqual(Builder::class);
        expect($db->orWhereIn("name", ["Taylor Otwell", "Rasmus Lerdouf"])::class)->toEqual(Builder::class);
        expect($db->whereNull("name")::class)->toEqual(Builder::class);
        expect($db->orWhereNull("name")::class)->toEqual(Builder::class);
        expect($db->whereNotNull("name")::class)->toEqual(Builder::class);
        expect($db->orWhereNotNull("name")::class)->toEqual(Builder::class);
        expect($db->having("count(user_id):count_users", ">", 1)::class)->toEqual(Builder::class);
        expect($db->orHaving("count(user_id):count_users", ">", 2)::class)->toEqual(Builder::class);
        expect($db->orderBy("name", "asc")::class)->toEqual(Builder::class);
        expect($db->limit(10)::class)->toEqual(Builder::class);
        expect($db->offset(10)::class)->toEqual(Builder::class);

    });

    test("select columns test", function (){
        expect(
            DB::table("users")
            ->select(['name', 'email:user_email', "min(age):user_min_age", "max(age):user_max_age", "avg(age):avg_user_age"])->toRawSql()
        )
        ->toContain("SELECT `name`, `email` as `user_email`, min(`age`) as `user_min_age`, max(`age`) as `user_max_age`, avg(`age`) as `avg_user_age` FROM `users`");

        expect(
            DB::table("posts")
                ->select(['user_id'])
                ->selectAggregate(count: "user_id:user_post_count")
                ->groupBy("user_id")
                ->having("user_post_count", ">", 1)
                ->orHaving("user_post_count", "!=", 4)
                ->toRawSql()
        )->toContain("SELECT `user_id`, count(`user_id`) as `user_post_count` FROM `posts` GROUP BY `user_id` HAVING `user_post_count` > ? OR `user_post_count` != ?");

    });

    test("distinct column test", function (){
        expect(
            DB::table("profiles")
                ->select(['city', 'country'])
                ->distinct()
                ->toRawSql()
        )->toContain("SELECT DISTINCT `city`, `country` FROM `profiles`");
    });

    test("SQl where clauses build test", function (){
        expect(
            DB::table("users")
            ->where("name", "umar")->orWhere("name", "ali")
            ->whereNull("verified_email")->orWhereNull("guest")
            ->whereNotNull("phone")->orWhereNotNull("email")
            ->whereIn("id", [1,2,3,4,5])->orWhereIn("id", [6,7,8,9])
            ->whereNotIn("id", [10,11,12])->orwhereNotIn("id", [13,14,15,16])
            ->toRawSql()
        )->toContain("SELECT * FROM `users` WHERE `name` = ? OR `name` = ? AND `verified_email` IS NULL OR `guest` IS NULL AND `phone` IS NOT NULL OR `email` IS NOT NULL AND `id` IN (?,?,?,?,?) OR `id` IN (?,?,?,?) AND `id` IN (?,?,?) OR `id` IN (?,?,?,?)");
    });

    test("join tables and relation test", function (){
        expect(
            DB::table("users:u")
                ->join("profiles:pr", "u.id", "pr.user_id")
                ->join("posts:po", 'u.id', 'po.user_id')
                ->toRawSql()
        )->toContain("SELECT * FROM `users` as `u` INNER JOIN `profiles` as `pr` ON `u`.`id` = `pr`.`user_id` INNER JOIN `posts` as `po` ON `u`.`id` = `po`.`user_id`");


        expect(
            DB::table("users")->leftJoin("profiles:pr", 'users.id', "pr.user_id")->toRawSql()
        )->toContain("SELECT * FROM `users` LEFT JOIN `profiles` as `pr` ON `users`.`id` = `pr`.`user_id`");

        expect(
            DB::table("users")->rightJoin("profiles:pr", 'users.id', "pr.user_id")->toRawSql()
        )->toContain("SELECT * FROM `users` RIGHT JOIN `profiles` as `pr` ON `users`.`id` = `pr`.`user_id`");

        expect(
            DB::table("users")->crossJoin("profiles:pr", 'users.id', "pr.user_id")->toRawSql()
        )->toContain("SELECT * FROM `users` CROSS JOIN `profiles` as `pr`");
    });


    test("sorting and limiting test", function (){
        expect(
            DB::table("users")
                ->orderBy("name")
                ->limit(10)
                ->offset(5)
                ->toRawSql()
        )->toContain("SELECT * FROM `users` ORDER BY `name` ASC LIMIT 5, 10");
    });


    /**
     *  -----------------------------------------
     *  Assume we will have total 100 records in our users table and the table structure of users contain verified_email column
     *  We only count the users who will verified in our application
     * 
     *  Assume 56 out of 100 users verified so the count method return 56
     * 
     *  If you don't have any users table or verified_email column in users table that you can skip this test because it will thrown an exception
     *  -----------------------------------------
     */
    test("count check", function (){
        try{
            // it could be return exception if you have not users table in your db and also not have 56 verified users.
            expect(
                DB::table("users")->whereNotNull("verified_email")->count()
            )->toBe(56);
        }catch(BuilderException $e){
            echo $e->getErrorMessage();
        }
    });

});


afterEach(function (){
    DB::resetConnection();
});



?>