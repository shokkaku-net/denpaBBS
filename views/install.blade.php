<!DOCTYPE html>
<html>

<head>
    <title>DenpaBBS Installer</title>
    <link class="linkstyle" rel="stylesheet" type="text/css" href="/static/css/futaclone.css" title="defaultcss">
</head>

<body>
    <h1>DenpaBBS Installer</h1>
    @if ($status === 'success')
        <div class="postblock">
            <h2>Installation Completed</h2>
            <ul>
                @foreach ($messages as $msg)
                    <li>{!! $msg !!}</li>
                @endforeach
            </ul>
            <p><a href="/intro/">Go to /intro/ board</a></p>
        </div>
    @elseif ($status === 'error')
        <div class="postblock" style="color: red;">
            <h2>Installation Failed</h2>
            <ul>
                @foreach ($messages as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!empty($errors))
        <div class="postblock" style="color: red;">
            <h2>Missing Requirements</h2>
            <ul>
                @foreach ($errors as $error)
                    <li>{!! $error !!}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <div class="prompt">
        Once you have a MySQL server set up with a basic username, password, and privileges, enter the credentials
        below.<br>
        This will also create a <i>conf.php</i> and a board called <i>/intro/</i>.
        <hr>

        <form method="post">
            <div>
                <label for="username">Username*:</label>
                <input type="text" id="username" name="username"
                    value="{{ $_POST['username'] ?? ($conf['mysqlDB']['username'] ?? '') }}" required>
            </div>
            <div>
                <label for="password">Password*:</label>
                <input type="text" id="password" name="password"
                    value="{{ $_POST['password'] ?? ($conf['mysqlDB']['password'] ?? '') }}" required>
            </div>
            <div>
                <label for="host">Domain/ip*:</label>
                <input type="text" id="host" name="host"
                    value="{{ $_POST['host'] ?? ($conf['mysqlDB']['host'] ?? '') }}" required>
            </div>
            <div>
                <label for="port">Port:</label>
                <input type="text" id="port" name="port"
                    value="{{ $_POST['port'] ?? ($conf['mysqlDB']['port'] ?? '') }}" required>
            </div>
            <div>
                <label for="databaseName">Database name*:</label>
                <input type="text" id="databaseName" name="databaseName"
                    value="{{ $_POST['databaseName'] ?? ($conf['mysqlDB']['databaseName'] ?? '') }}" required>
            </div>
            <div></div>
            <div>
                <label for="adminPassword">Admin Password*:</label>
                <input type="password" id="adminPassword" name="adminPassword"
                    value="{{ $_POST['adminPassword'] ?? '' }}" required>
            </div>
            <div>
                <label for="domain">Domain*:</label>
                <input type="text" id="domain" name="domain"
                    value="{{ $_POST['domain'] ?? ($conf['domain'] ?? $_SERVER['HTTP_HOST']) }}" required>
            </div>


            <div>
                <button type="submit" name="install">Install</button>
            </div>
        </form>
    </div>
</body>

</html>