<?php
session_start();

// VERIFICA SE O ADMINISTRADOR ESTÁ LOGADO
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

include '../includes/db_connect.php';
include '../includes/generate_credentials.php';

// ID DO ADMINISTRADOR LOGADO
$adminId = $_SESSION['admin_id'];

// MENSAGEM PARA EXIBIR FEEDBACK
$message = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['generate_user'])) {
        $name = $_POST['name'] ?? '';
        list($username, $password) = generateCredentials(); 
        $valid_until = $_POST['valid_until'] ?? date('Y-m-d', strtotime('+1 month')); // DEFINE UM MÊS COMO PADRÃO

        // VERIFICA O NÚMERO DE CRÉDITOS DISPONÍVEIS
        $creditsQuery = "SELECT credits FROM admin_credits WHERE admin_id = ?";
        $stmt = $conn->prepare($creditsQuery);
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $stmt->bind_result($credits);
        $stmt->fetch();
        $stmt->close();

        if ($credits > 0) {
            // INSERE O NOVO USUÁRIO NO BANCO DE DADOS
            $message = insertUser($name, $username, $password, $valid_until, $adminId, $conn);
            if (strpos($message, 'sucesso') !== false) {
                // DECREMENTA OS CRÉDITOS
                $updateCreditsQuery = "UPDATE admin_credits SET credits = credits - 1 WHERE admin_id = ?";
                $stmt = $conn->prepare($updateCreditsQuery);
                $stmt->bind_param('i', $adminId);
                $stmt->execute();
                $stmt->close();
                
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            $message = "Créditos insuficientes para criar um novo usuário.";
        }
    }

    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'] ?? null;
        if ($userId) {
            $message = deleteUser($userId, $conn);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = "ID do usuário não fornecido.";
        }
    }

    if (isset($_POST['update_validity'])) {
        $userId = $_POST['user_id'] ?? null;
        $newValidity = $_POST['valid_until'] ?? '';
        if ($userId && $newValidity) {
            $message = updateUserValidity($userId, $newValidity, $conn);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = "ID do usuário ou nova validade não fornecidos.";
        }
    }
}

// CONSULTA APENAS OS USUÁRIOS CRIADOS PELO ADMINISTRADOR LOGADO
$sql = "SELECT * FROM users WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $adminId);
$stmt->execute();
$result = $stmt->get_result();

// CONSULTA CRÉDITOS DISPONÍVEIS PARA O ADMINISTRADOR
$creditsQuery = "SELECT credits FROM admin_credits WHERE admin_id = ?";
$stmt = $conn->prepare($creditsQuery);
$stmt->bind_param('i', $adminId);
$stmt->execute();
$stmt->bind_result($credits);
if (!$stmt->fetch()) {
    $credits = 0;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Administração</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <script>
        function showPopup(id, name, username, password, link, validity) {
            document.getElementById('popup-id').innerText = id;
            document.getElementById('popup-name').innerText = name;
            document.getElementById('popup-username').innerText = username;
            document.getElementById('popup-password').innerText = password;
            document.getElementById('popup-link').innerText = link;
            document.getElementById('popup-link').setAttribute('href', link);
            document.getElementById('popup-validity').value = validity;
            document.getElementById('popup-user-id').value = id;
            document.getElementById('popup').classList.add('active');
        }

        function closePopup() {
            document.getElementById('popup').classList.remove('active');
        }

        function showGenerateUserPopup() {
            document.getElementById('generate-user-popup').classList.add('active');
        }

        function closeGenerateUserPopup() {
            document.getElementById('generate-user-popup').classList.remove('active');
        }

        function confirmDelete() {
            return confirm("Você realmente quer excluir esse usuário?");
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>
            Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?>!
            <a href="logout.php">Sair</a>
        </h1>

        <h2>Créditos Disponíveis: <?php echo htmlspecialchars($credits ?? '0'); ?></h2>
        <button onclick="showGenerateUserPopup()">Gerar Novo Usuário</button>

        <?php if (isset($message)): ?>
            <div class="<?php echo strpos($message, 'Error') === false ? 'message' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <h2>Usuários Registrados</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Username</th>
                <th>Password</th>
                <th>Validade</th>
                <th>Ações</th>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="<?php echo (strtotime($row['valid_until']) < time()) ? 'expired' : ''; ?>">
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['password']); ?></td>
                        <td><?php echo htmlspecialchars($row['valid_until']); ?></td>
                        <td>
                        <button type="button" onclick="showPopup(
                        '<?php echo htmlspecialchars($row['id']); ?>',
                        '<?php echo htmlspecialchars($row['name']); ?>',
                        '<?php echo htmlspecialchars($row['username']); ?>',
                        '<?php echo htmlspecialchars($row['password']); ?>',
                        '<?php echo htmlspecialchars('http://SEUSOTE.COM/usuario=' . urlencode($row['username']) . '&password=' . urlencode($row['password'])); ?>',
                        '<?php echo htmlspecialchars($row['valid_until']); ?>'
                    )">Ver Detalhes</button>

                            <form method="POST" style="display:inline-block;" onsubmit="return confirmDelete();">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                <button type="submit" name="delete_user" class="btn-delete">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Nenhum usuário registrado.</td>
                </tr>
            <?php endif; ?>
        </table>

        <div id="popup" class="popup">
            <div class="popup-content">
                <span class="popup-close" onclick="closePopup()">X</span>
                <h2>Detalhes do Usuário</h2>
                <p>ID: <span id="popup-id"></span></p>
                <p>Nome: <span id="popup-name"></span></p>
                <p>Username: <span id="popup-username"></span></p>
                <p>Password: <span id="popup-password"></span></p>
                <p>Link: <a id="popup-link" href="#" target="_blank">Acessar</a></p>
                <form method="POST">
                    <input type="hidden" name="user_id" id="popup-user-id">
                    <label for="popup-validity">Atualizar Validade:</label>
                    <input type="date" id="popup-validity" name="valid_until">
                    <button type="submit" name="update_validity">Atualizar</button>
                </form>
            </div>
        </div>

        <div id="generate-user-popup" class="popup">
            <div class="popup-content">
                <span class="popup-close" onclick="closeGenerateUserPopup()">X</span>
                <h2>Gerar Novo Usuário</h2>
                <form method="POST">
                    <label for="name">Nome do Usuário:</label>
                    <input type="text" id="name" name="name" required>
                    <label for="valid_until">Validade:</label>
                    <input type="date" id="valid_until" name="valid_until">
                    <button type="submit" name="generate_user">Gerar Usuário</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
