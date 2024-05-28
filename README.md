```

ssh -A andrii.leonov@bastion-v2.pdffiller.com -i ~/.ssh/id_rsa
ssh ubuntu@10.20.105.147
sudo su

docker run --name some-mysql -e MYSQL_ROOT_PASSWORD=my-secret-pw -d mysql
docker exec -it ad229c5c7929 bash
mysql -u root -p
my-secret-pw
CREATE DATABASE stat_parser;
USE stat_parser;



CREATE TABLE IF NOT EXISTS `usage` (`id` int(11) NOT NULL auto_increment,`key` varchar(32)  NOT NULL default '',`size` int(11) NOT NULL default '0', PRIMARY KEY  (`id`));

CREATE TABLE IF NOT EXISTS `last_date` (`id` int(11) NOT NULL auto_increment,`date` varchar(10)  NOT NULL default '',PRIMARY KEY  (`id`));

CREATE TABLE IF NOT EXISTS `last_url` (`id` int(11) NOT NULL auto_increment,`url` varchar(250)  NOT NULL default '',PRIMARY KEY  (`id`));


docker inspect CONTAINER_ID --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'



docker run -e SERVICE_NAME="st-parser" -e AWS_ACCESS_KEY_ID="" -e AWS_SECRET_ACCESS_KEY="" -e ARN_ROLE_READ="arn:aws:iam::811130481316:role/ppf-st-parser-s3-role" -e BUCKET_NAME_READ="ppf-logs-20190701122158291000000001" -e REGION_READ="us-east-1" -e STATISTIC_FOLDER_PATH="fs_s3_statistic/ppf-fileservice-20180927091328208800000001/fs_s3_inventory/" -e DB_HOST=172.17.0.2 -it --name parser-2024-03-05 --rm statistic-aggregator:v2 php app.php --dates=2024-03-05
```
