# Personal maps timeline

ภาษาไทย

แสดงไทม์ไลน์ของ Google บนเครื่องคอมพิวเตอร์ของคุณ. ดูวีดีโอตัวอย่างที่ https://youtu.be/345UmtRfIDU

## ติดตั้ง

### ติดตั้งโดยใช้คำสั่ง git
การติดตั้งผ่านการใช้คำสั่ง git เป็นวิธีที่แนะนำ เพราะจะง่ายในขั้นตอนอัปเดท. เครื่องของคุณต้องมี[โปรแกรม git](https://git-scm.com/downloads) ติดตั้งพร้อมไว้ก่อนอยู่แล้ว.

1. ติดตั้งโดยใช้คำสั่ง `git clone https://github.com/Rundiz/personal-maps-timeline.git .` บนโฟลเดอร์ที่คุณต้องการเรียกใช้งานผ่าน web server และ PHP ได้.

### ติดตั้งโดยใช้ Download ธรรมดา
1. ดาวน์โหลดโดยใช้ปุ่ม Code ด้านบนแล้วเลือก Download Zip.
2. แตกไฟล์ออกบนตำแหน่งที่สามารถเรียกใช้ผ่าน web server และ PHP ได้.

###  ขั้นตอนติดตั้งลำดับต่อมา
หลังจากเลือกว่าจะติดตั้งโดยใช้วิธีใดแล้ว ลำดับต่อมาให้ทำดังต่อไปนี้.
1. สร้างฐานข้อมูลบน MariaDB, MySQL แล้วนำเข้าไฟล์ mariadb-structure.sql.
2. คัดลอกไฟล์ default.config.php ไปเป็นไฟล์ config.php แล้วแก้ไขตั้งค่าต่างๆ.
3. รันคำสั่ง `composer install`.

## อัปเดท

### อัปเดทโดยการติดตั้งที่ใช้คำสั่ง git
หากคุณติดตั้งผ่านการใช้คำสั่ง git ให้ดำเนินการอัปเดทอัตโนมัติได้ดังต่อไปนี้.

1. รันคำสั่ง `git pull`.

### อัปเดทโดยการติดตั้งที่ใช้ Download ธรรมดา

1. ให้ดำเนินการดาวน์โหลดโดยวิธีเดียวกันกับขั้นตอนการติดตั้ง แล้วแตกไฟล์ออกมาทับในตำแหน่งเดิม โดยอาจจะลบไฟล์เก่าทั้งหมดก่อน ยกเว้นไฟล์ config.php เอาไว้ จากนั้นจึงค่อยแตกไฟล์ออกมา.

### ขั้นตอนอัปเดทลำดับต่อมา
ไม่ว่าจะดำเนินการติดตั้งหรืออัปเดทด้วยวิธีใด ขั้นตอนสุดท้ายนี้สำคัญเสมอ. เครื่องของคุณต้องติดตั้ง[โปรแกรม Composer](https://getcomposer.org/) เอาไว้อยู่ก่อนแล้ว.

1. รันคำสั่ง `composer update`.

---

English (translated by Google Translate)

Show Google Timeline on your computer. Watch the sample video at https://youtu.be/345UmtRfIDU

## Install

### Install using git command
Installing using git command is the recommended method because it will be easier in the update step. Your machine must have [git program](https://git-scm.com/downloads) installed beforehand.

1. Install using the command `git clone https://github.com/Rundiz/personal-maps-timeline.git .` on the folder where you want to call it via web server and PHP.

### Install using normal download
1. Download using the Code button above and select Download Zip.
2. Extract the file to a location where it can be called via web server and PHP.

### Subsequent installation steps
After choosing which method to install, do the following.
1. Create a database on MariaDB, MySQL and import the mariadb-structure.sql file.
2. Copy the default.config.php file to the config.php file and edit the settings.
3. Run the `composer install` command.

## Update

### Update by installing using git
If you installed using git, you can perform automatic updates as follows.

1. Run the `git pull` command.

### Update by installing using normal download

1. Download the same way as the installation steps and extract the files to the original location. You may want to delete all the old files first, except for the config.php file, and then extract the files.

### Next update steps
Whether you install or update using any method, this last step is always important. Your machine must have [Composer](https://getcomposer.org/) installed.

1. Run the `composer update` command.