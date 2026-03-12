# FC-Apps DB Troubleshooting

Run these commands on `FC-Apps` in an elevated PowerShell session.

## 1. Verify host info, app config, and DB reachability

```powershell
cd C:\inetpub\wwwroot\htdocs\pam\v1

hostname
[System.Net.Dns]::GetHostEntry($env:COMPUTERNAME).HostName

Get-NetIPAddress -AddressFamily IPv4 |
  Where-Object { $_.IPAddress -notlike '169.254*' } |
  Format-Table InterfaceAlias,IPAddress -Auto

Get-Content .\backend\.env | Select-Object -First 5

Test-NetConnection 192.168.3.4 -Port 3306
```

## 2. Test the app DB login from FC-Apps

```powershell
$mysql = "C:\xampp\mysql\bin\mysql.exe"
if (-not (Test-Path $mysql)) {
  $mysql = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
}

& $mysql -h 192.168.3.4 -u pr -p -D partsam -e "SELECT 1;"
& $mysql -h 127.0.0.1   -u pr -p -D partsam -e "SELECT 1;"
& $mysql -h localhost   -u pr -p -D partsam -e "SELECT 1;"
```
xxxxxxxxxxx
```
$mysql = "C:\xampp\mysql\bin\mysql.exe"
  if (-not (Test-Path $mysql)) {
    $mysql = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
  }

  & $mysql -h 127.0.0.1 -u root -p -e "SELECT User,Host,plugin,account_locked FROM mysql.user WHERE User='pr';"
  & $mysql -h 127.0.0.1 -u root -p -e "CREATE USER IF NOT EXISTS 'pr'@'localhost' IDENTIFIED BY '<password-from-backend-env>'; ALTER USER 'pr'@'localhost' IDENTIFIED BY '<password-from-backend-env>'; GRANT ALL PRIVILEGES ON
  partsam.* TO 'pr'@'localhost'; FLUSH PRIVILEGES;"
  & $mysql -h 127.0.0.1 -u root -p -e "SHOW GRANTS FOR 'pr'@'localhost';"
```
## 3. If `127.0.0.1` or `localhost` works, change the app to use local MySQL

```powershell
notepad C:\inetpub\wwwroot\htdocs\pam\v1\backend\.env
```

Change:

```text
DB_HOST=192.168.3.4
```

To:

```text
DB_HOST=127.0.0.1
```

Then recycle IIS:

```powershell
Import-Module WebAdministration
Get-ChildItem IIS:\AppPools | Format-Table Name,State -Auto
Restart-WebAppPool -Name "<your-app-pool-name>"
```

If you do not know the app pool name:

```powershell
iisreset
```

## 4. If all three DB tests fail, fix MySQL grants on the MySQL server

If MySQL is on `FC-Apps` itself, run:

```powershell
& $mysql -h 127.0.0.1 -u root -p -e "SELECT User,Host FROM mysql.user WHERE User='pr';"
```

Then grant the app user access from the app server host:

```powershell
& $mysql -h 127.0.0.1 -u root -p -e "CREATE USER IF NOT EXISTS 'pr'@'Fc-Apps-01.FCCL.local' IDENTIFIED BY '<app-password>'; GRANT ALL PRIVILEGES ON partsam.* TO 'pr'@'Fc-Apps-01.FCCL.local'; CREATE USER IF NOT EXISTS 'pr'@'127.0.0.1' IDENTIFIED BY '<app-password>'; GRANT ALL PRIVILEGES ON partsam.* TO 'pr'@'127.0.0.1'; CREATE USER IF NOT EXISTS 'pr'@'localhost' IDENTIFIED BY '<app-password>'; GRANT ALL PRIVILEGES ON partsam.* TO 'pr'@'localhost'; FLUSH PRIVILEGES;"
```

## 5. Re-test after the change

```powershell
& $mysql -h 127.0.0.1 -u pr -p -D partsam -e "SELECT 1;"
Invoke-WebRequest http://localhost/api/settings -UseBasicParsing
```

## Expected outcome

The fastest likely fix on `FC-Apps` is:

1. Confirm whether MySQL accepts `127.0.0.1` or `localhost`.
2. If yes, set `DB_HOST=127.0.0.1` in `backend\.env`.
3. Recycle IIS.
4. Re-test `/api/settings`.

If local MySQL still rejects the login, update the MySQL grants for the `pr` user on the MySQL server.
