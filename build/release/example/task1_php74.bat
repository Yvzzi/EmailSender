@echo off

..\php74\php ../build/EmailSender_packed.phar -c mailSetting.json -f task/task1.json
pause