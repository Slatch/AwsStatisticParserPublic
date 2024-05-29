```

ssh -A andrii.leonov@bastion-v2.pdffiller.com -i ~/.ssh/id_rsa
ssh ubuntu@10.20.105.147
sudo su

git clone -b mysql https://github.com/Slatch/AwsStatisticParserPublic parser
docker build -t statistic-aggregator:v2 ./AwsStatisticParserPublic/

docker run --name db-mysql -e MYSQL_ROOT_PASSWORD=my-secret-pw -d mysql
docker exec -it db-mysql bash
mysql -u root -p
my-secret-pw
CREATE DATABASE stat_parser;
USE stat_parser;

CREATE TABLE IF NOT EXISTS `usage` (`key` varchar(32)  NOT NULL, `size` int(11) NOT NULL) PARTITION BY RANGE( `size` ) ( PARTITION p0 VALUES LESS THAN (500), PARTITION p1 VALUES LESS THAN (4000), PARTITION p2 VALUES LESS THAN (8000), PARTITION p3 VALUES LESS THAN (16000), PARTITION p4 VALUES LESS THAN (34000), PARTITION p5 VALUES LESS THAN (56000), PARTITION p6 VALUES LESS THAN (90000), PARTITION p7 VALUES LESS THAN (131072), PARTITION p8 VALUES LESS THAN (210000), PARTITION p9 VALUES LESS THAN (350000), PARTITION p10 VALUES LESS THAN (1000000), PARTITION p11 VALUES LESS THAN MAXVALUE);

CREATE TABLE IF NOT EXISTS `usage` (
    `id` int(13) unsigned NOT NULL auto_increment,
    `key` varchar(32)  NOT NULL default '',
    `size` int(11) NOT NULL default '0',
    PRIMARY KEY  (`id`)
)
 PARTITION BY RANGE( `size` ) (
        PARTITION p0 VALUES LESS THAN (500),
        PARTITION p1 VALUES LESS THAN (4000),
        PARTITION p2 VALUES LESS THAN (8000),
        PARTITION p3 VALUES LESS THAN (16000),
        PARTITION p4 VALUES LESS THAN (34000),
        PARTITION p5 VALUES LESS THAN (56000),
        PARTITION p6 VALUES LESS THAN (90000),
        PARTITION p7 VALUES LESS THAN (131072),
        PARTITION p8 VALUES LESS THAN (210000),
        PARTITION p9 VALUES LESS THAN (350000),
        PARTITION p10 VALUES LESS THAN (1000000),
        PARTITION p11 VALUES LESS THAN MAXVALUE
    );


CREATE TABLE IF NOT EXISTS `last_date` (`id` int(5) unsigned NOT NULL auto_increment, `date` varchar(10)  NOT NULL default '', PRIMARY KEY  (`id`));

CREATE TABLE IF NOT EXISTS `last_url` (`id` int(5) unsigned NOT NULL auto_increment, `url` varchar(250)  NOT NULL default '', PRIMARY KEY  (`id`));

docker inspect CONTAINER_ID --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'

docker run -e SERVICE_NAME="st-parser" -e BATCH_SIZE="500" -e AWS_ACCESS_KEY_ID="" -e AWS_SECRET_ACCESS_KEY="" -e ARN_ROLE_READ="arn:aws:iam::811130481316:role/ppf-st-parser-s3-role" -e BUCKET_NAME_READ="ppf-logs-20190701122158291000000001" -e REGION_READ="us-east-1" -e STATISTIC_FOLDER_PATH="fs_s3_statistic/ppf-fileservice-20180927091328208800000001/fs_s3_inventory/" -e DB_HOST=172.17.0.2 -d -it --name p-03-05 --rm statistic-aggregator:v2 php app.php --dates=2024-03-05
```


SET unique_checks=0; SET foreign_key_checks=0;
..insert sql code here..
SET unique_checks=1; SET foreign_key_checks=1;
