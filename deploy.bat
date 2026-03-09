@echo off
title Parking System - One Click Deploy

echo ===============================
echo PARKING SYSTEM DEPLOY
echo ===============================

cd /d C:\xampp_new\htdocs\parking-system

echo.
set /p msg=Enter commit message: 

git add .
git commit -m "%msg%"
git push

echo.
echo ===============================
echo DEPLOY COMPLETE
echo ===============================
pause