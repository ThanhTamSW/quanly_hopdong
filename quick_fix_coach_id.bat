@echo off
echo Running migration to allow coach_id = NULL...
echo.

mysql -u root quanly_hopdong < migrations\allow_coach_id_null.sql

echo.
echo Done! Press any key to exit...
pause

