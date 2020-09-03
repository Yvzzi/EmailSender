@echo off

..\php74\php ../build/EmailSender_packed.phar -c mailSetting.json -f task/task2.json -o dir
pause