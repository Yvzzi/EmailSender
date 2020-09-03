@echo off

..\php74\php ../build/EmailSender_packed.phar -c mailSetting.json -f task/task3.json -o dir
pause