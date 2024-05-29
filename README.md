```shell
ssh -A andrii.leonov@bastion-v2.pdffiller.com -i ~/.ssh/id_rsa
ssh ubuntu@10.20.105.147
sudo su
```

```shell
git clone -b mysql https://github.com/Slatch/AwsStatisticParserPublic parser
docker build -t statistic-aggregator:v2 ./parser/
```

```shell
docker run --name mysql-db -e MYSQL_ROOT_PASSWORD=my-secret-pw -d mysql
docker exec -it mysql-db bash
mysql -u root -p
my-secret-pw
```
```mysql
CREATE DATABASE stat_parser;
USE stat_parser;
```

```mysql
CREATE TABLE IF NOT EXISTS `usage` (`key` varchar(32)  NOT NULL, `size` int(11) NOT NULL) PARTITION BY RANGE( `size` ) ( PARTITION p0 VALUES LESS THAN (500), PARTITION p1 VALUES LESS THAN (4000), PARTITION p2 VALUES LESS THAN (8000), PARTITION p3 VALUES LESS THAN (16000), PARTITION p4 VALUES LESS THAN (34000), PARTITION p5 VALUES LESS THAN (56000), PARTITION p6 VALUES LESS THAN (90000), PARTITION p7 VALUES LESS THAN (131072), PARTITION p8 VALUES LESS THAN (210000), PARTITION p9 VALUES LESS THAN (350000), PARTITION p10 VALUES LESS THAN (1000000), PARTITION p11 VALUES LESS THAN MAXVALUE);

CREATE TABLE IF NOT EXISTS `last_date` (`id` int(5) unsigned NOT NULL auto_increment, `date` varchar(10)  NOT NULL default '', PRIMARY KEY  (`id`));

CREATE TABLE IF NOT EXISTS `last_url` (`id` int(5) unsigned NOT NULL auto_increment, `url` varchar(250)  NOT NULL default '', PRIMARY KEY  (`id`));

SET GLOBAL unique_checks=0; SET GLOBAL foreign_key_checks=0;

CREATE TABLE IF NOT EXISTS `usage` (
    `key` varchar(32)  NOT NULL default '',
    `size` int(11) NOT NULL default '0'
) PARTITION BY RANGE( `size` ) (
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

CREATE TABLE IF NOT EXISTS `last_date` (
    `id` int(5) unsigned NOT NULL auto_increment,
    `date` varchar(10)  NOT NULL default '',
    PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS `last_url` (
    `id` int(5) unsigned NOT NULL auto_increment,
    `url` varchar(250)  NOT NULL default '',
    PRIMARY KEY  (`id`)
);
```

```shell
docker inspect mysql-db --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'

docker run -e SERVICE_NAME="st-parser" -e BATCH_SIZE="500" -e AWS_ACCESS_KEY_ID="" -e AWS_SECRET_ACCESS_KEY="" -e ARN_ROLE_READ="arn:aws:iam::811130481316:role/ppf-st-parser-s3-role" -e BUCKET_NAME_READ="ppf-logs-20190701122158291000000001" -e REGION_READ="us-east-1" -e STATISTIC_FOLDER_PATH="fs_s3_statistic/ppf-fileservice-20180927091328208800000001/fs_s3_inventory/" -e DB_HOST=172.17.0.2 -e DB_SUFFIX=03_05 -d -it --name p03-05 --rm statistic-aggregator:v2 php app.php --dates=2024-03-05
docker run -e SERVICE_NAME="st-parser" -e BATCH_SIZE="500" -e AWS_ACCESS_KEY_ID="" -e AWS_SECRET_ACCESS_KEY="" -e ARN_ROLE_READ="arn:aws:iam::811130481316:role/ppf-st-parser-s3-role" -e BUCKET_NAME_READ="ppf-logs-20190701122158291000000001" -e REGION_READ="us-east-1" -e STATISTIC_FOLDER_PATH="fs_s3_statistic/ppf-fileservice-20180927091328208800000001/fs_s3_inventory/" -e DB_HOST=172.17.0.2 -e DB_SUFFIX=03_06 -d -it --name p03-06 --rm statistic-aggregator:v2 php app.php --dates=2024-03-06
docker run -e SERVICE_NAME="st-parser" -e BATCH_SIZE="500" -e AWS_ACCESS_KEY_ID="" -e AWS_SECRET_ACCESS_KEY="" -e ARN_ROLE_READ="arn:aws:iam::811130481316:role/ppf-st-parser-s3-role" -e BUCKET_NAME_READ="ppf-logs-20190701122158291000000001" -e REGION_READ="us-east-1" -e STATISTIC_FOLDER_PATH="fs_s3_statistic/ppf-fileservice-20180927091328208800000001/fs_s3_inventory/" -e DB_HOST=172.17.0.2 -e DB_SUFFIX=03_07 -d -it --name p03-07 --rm statistic-aggregator:v2 php app.php --dates=2024-03-07
docker run -e SERVICE_NAME="st-parser" -e BATCH_SIZE="500" -e AWS_ACCESS_KEY_ID="" -e AWS_SECRET_ACCESS_KEY="" -e ARN_ROLE_READ="arn:aws:iam::811130481316:role/ppf-st-parser-s3-role" -e BUCKET_NAME_READ="ppf-logs-20190701122158291000000001" -e REGION_READ="us-east-1" -e STATISTIC_FOLDER_PATH="fs_s3_statistic/ppf-fileservice-20180927091328208800000001/fs_s3_inventory/" -e DB_HOST=172.17.0.2 -e DB_SUFFIX=03_08 -d -it --name p03-08 --rm statistic-aggregator:v2 php app.php --dates=2024-03-08
docker run -e SERVICE_NAME="st-parser" -e BATCH_SIZE="500" -e AWS_ACCESS_KEY_ID="" -e AWS_SECRET_ACCESS_KEY="" -e ARN_ROLE_READ="arn:aws:iam::811130481316:role/ppf-st-parser-s3-role" -e BUCKET_NAME_READ="ppf-logs-20190701122158291000000001" -e REGION_READ="us-east-1" -e STATISTIC_FOLDER_PATH="fs_s3_statistic/ppf-fileservice-20180927091328208800000001/fs_s3_inventory/" -e DB_HOST=172.17.0.2 -e DB_SUFFIX=03_09 -d -it --name p03-09 --rm statistic-aggregator:v2 php app.php --dates=2024-03-09
docker run -e SERVICE_NAME="st-parser" -e BATCH_SIZE="500" -e AWS_ACCESS_KEY_ID="" -e AWS_SECRET_ACCESS_KEY="" -e ARN_ROLE_READ="arn:aws:iam::811130481316:role/ppf-st-parser-s3-role" -e BUCKET_NAME_READ="ppf-logs-20190701122158291000000001" -e REGION_READ="us-east-1" -e STATISTIC_FOLDER_PATH="fs_s3_statistic/ppf-fileservice-20180927091328208800000001/fs_s3_inventory/" -e DB_HOST=172.17.0.2 -e DB_SUFFIX=03_10 -d -it --name p03-10 --rm statistic-aggregator:v2 php app.php --dates=2024-03-10
docker run -e SERVICE_NAME="st-parser" -e BATCH_SIZE="500" -e AWS_ACCESS_KEY_ID="" -e AWS_SECRET_ACCESS_KEY="" -e ARN_ROLE_READ="arn:aws:iam::811130481316:role/ppf-st-parser-s3-role" -e BUCKET_NAME_READ="ppf-logs-20190701122158291000000001" -e REGION_READ="us-east-1" -e STATISTIC_FOLDER_PATH="fs_s3_statistic/ppf-fileservice-20180927091328208800000001/fs_s3_inventory/" -e DB_HOST=172.17.0.2 -e DB_SUFFIX=03_11 -d -it --name p03-11 --rm statistic-aggregator:v2 php app.php --dates=2024-03-11
```

```mysql
SELECT * FROM `usage` PARTITION (p0) LIMIT 10;
```

CREATE TABLE IF NOT EXISTS `usage_03_05` (`key` varchar(32)  NOT NULL, `size` int(11) NOT NULL);
CREATE TABLE IF NOT EXISTS `usage_03_06` (`key` varchar(32)  NOT NULL, `size` int(11) NOT NULL);
CREATE TABLE IF NOT EXISTS `usage_03_07` (`key` varchar(32)  NOT NULL, `size` int(11) NOT NULL);
CREATE TABLE IF NOT EXISTS `usage_03_08` (`key` varchar(32)  NOT NULL, `size` int(11) NOT NULL);
CREATE TABLE IF NOT EXISTS `usage_03_09` (`key` varchar(32)  NOT NULL, `size` int(11) NOT NULL);
CREATE TABLE IF NOT EXISTS `usage_03_10` (`key` varchar(32)  NOT NULL, `size` int(11) NOT NULL);
CREATE TABLE IF NOT EXISTS `usage_03_11` (`key` varchar(32)  NOT NULL, `size` int(11) NOT NULL);

docker run -e SERVICE_NAME="st-parser" -e BATCH_SIZE="500" -e AWS_ACCESS_KEY_ID="" -e AWS_SECRET_ACCESS_KEY="" -e ARN_ROLE_READ="arn:aws:iam::811130481316:role/ppf-st-parser-s3-role" -e BUCKET_NAME_READ="ppf-logs-20190701122158291000000001" -e REGION_READ="us-east-1" -e STATISTIC_FOLDER_PATH="fs_s3_statistic/ppf-fileservice-20180927091328208800000001/fs_s3_inventory/" -e DB_HOST=172.17.0.2 -e DB_SUFFIX=03_05 -it --name p03-05 --rm statistic-aggregator:v2 php app.php --dates=2024-03-05


