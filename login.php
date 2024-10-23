<?php
session_start();
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT id, username FROM admin WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($admin_id, $admin_username);
        $stmt->fetch();
        
        $_SESSION['admin_id'] = $admin_id;
        $_SESSION['admin_username'] = $admin_username;
        $_SESSION['admin_logged_in'] = true;

        echo '<pre>'; print_r($_SESSION); echo '</pre>'; 

        header('Location: index.php');
        exit();
    } else {
        $message = 'Usuário ou senha inválidos.';
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Miraca IPTV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="loginstyle.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h1>Login</h1>
            <form method="POST">
                <label for="username">Usuário:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Entrar</button>
            </form>
        </div>

        <div class="menu">
            <button onclick="mostrarPopup('creditos-popup')">Valores de Créditos</button>
            <button onclick="mostrarPopup('comprar-popup')">Comprar Créditos</button>
        </div>

        <div id="creditos-popup" class="popup">
            <div class="popup-content">
                <h2>Valores de Créditos</h2>
                <ul id="creditos-list">
                </ul>
                <button class="close-btn" onclick="fecharPopup('creditos-popup')">Fechar</button>
            </div>
        </div>

        <div id="comprar-popup" class="popup">
            <div class="popup-content">
                <h2>Comprar Créditos</h2>
                <p>Escolha a quantidade de créditos que deseja comprar:</p>
                <form id="compra-form">
                    <label for="quantidade">Quantidade:</label>
                    <input type="number" id="quantidade" name="quantidade" min="1" value="1">
                    <button type="button" onclick="enviarWhatsApp()">Comprar</button>
                </form>
                <button class="close-btn" onclick="fecharPopup('comprar-popup')">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        const valoresCreditos = [
            { quantidade: 1, preco: 30, whatsapp: '5513997791196' },
            { quantidade: 5, preco: 40, whatsapp: '5513997791196' },
            { quantidade: 10, preco: 60, whatsapp: '5513997791196' },
            { quantidade: 20, preco: 110, whatsapp: '5513997791196' }
        ];

        function mostrarPopup(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function fecharPopup(id) {
            document.getElementById(id).style.display = 'none';
        }

        function carregarValoresCreditos() {
            const lista = document.getElementById('creditos-list');
            lista.innerHTML = '';
            valoresCreditos.forEach(credito => {
                const item = document.createElement('li');
                item.innerHTML = `
                    ${credito.quantidade} créditos por R$${credito.preco.toFixed(2)}
                    <a href="https://wa.me/${credito.whatsapp}?text=Ol%C3%A1%2C%20quero%20comprar%20${credito.quantidade}%20cr%C3%A9ditos%20por%20R%24${credito.preco.toFixed(2)}" class="whatsapp-btn">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                `;
                lista.appendChild(item);
            });
        }

        function enviarWhatsApp() {
            const quantidade = document.getElementById('quantidade').value;
            const valorCredito = valoresCreditos.find(v => v.quantidade == quantidade);
            if (valorCredito) {
                window.open(`https://wa.me/${valorCredito.whatsapp}?text=Ol%C3%A1%2C%20quero%20comprar%20${quantidade}%20cr%C3%A9ditos%20por%20R%24${valorCredito.preco.toFixed(2)}`, '_blank');
            }
        }

        window.onload = carregarValoresCreditos;
    </script>
</body>
</html>
