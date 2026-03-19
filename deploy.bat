@echo off
title Parking System - Deploy

echo ===============================
echo PARKING SYSTEM DEPLOY
echo ===============================

cd /d C:\xampp_new\htdocs\parking-system

echo.
set /p msg=Enter commit message:
if "%msg%"=="" set msg=auto update

git add .
git commit -m "%msg%" || echo No changes to commit
git push origin main

echo.
echo ===============================
echo DEPLOY COMPLETE (PARKING)
echo ===============================

pause