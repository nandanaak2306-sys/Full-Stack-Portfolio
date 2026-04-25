<?php

session_start();

include 'db.php'; // Existing database connection

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$feedback_message = '';
$feedback_type = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {

    // 1. CSRF Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token mismatch. Request blocked.');
    }

    // 2. Input Validation & Length Limits
    $title = trim($_POST['title'] ?? '');
    $tech  = trim($_POST['tech'] ?? '');
    $desc  = trim($_POST['description'] ?? '');

    $errors = [];
    if (strlen($title) === 0 || strlen($title) > 150) {
        $errors[] = "Title must be between 1 and 150 characters.";
    }
    if (strlen($tech) === 0 || strlen($tech) > 200) {
        $errors[] = "Tech stack must be between 1 and 200 characters.";
    }
    if (strlen($desc) === 0 || strlen($desc) > 1000) {
        $errors[] = "Description must be between 1 and 1000 characters.";
    }

    if (empty($errors)) {
        // 3. Prepared Statement — prevents SQL Injection
        $stmt = $conn->prepare("INSERT INTO project_details (title, tech_stack, description) VALUES (?, ?, ?)");

        if ($stmt) {
            $stmt->bind_param("sss", $title, $tech, $desc);

            if ($stmt->execute()) {
                $stmt->close();
                // Regenerate CSRF token after successful submit
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                // PRG pattern: redirect to clear POST data
                header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
                exit();
            } else {
                $feedback_message = "Database error. Please try again later.";
                $feedback_type = 'error';
                $stmt->close();
            }
        } else {
            $feedback_message = "Could not prepare statement. Check DB connection.";
            $feedback_type = 'error';
        }
    } else {
        $feedback_message = implode(' ', $errors);
        $feedback_type = 'error';
    }
}

// Show success message after redirect
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $feedback_message = "Project saved successfully!";
    $feedback_type = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- FIX: Added viewport meta tag for mobile responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nandana AK | Portfolio</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a6ef5;
            --primary-light: #eaf0ff;
            --dark: #0f172a;
            --mid: #334155;
            --light: #f1f5f9;
            --white: #ffffff;
            --success-bg: #d1fae5;
            --success-text: #065f46;
            --error-bg: #fee2e2;
            --error-text: #991b1b;
            --border: #e2e8f0;
            --radius: 14px;
            --shadow: 0 4px 24px rgba(0,0,0,0.07);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.7;
        }

        /* ---- Header ---- */
        header {
            background: var(--dark);
            color: white;
            padding: 60px 20px 50px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        header::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(26,110,245,0.25) 0%, transparent 70%);
            top: -150px; left: 50%; transform: translateX(-50%);
            pointer-events: none;
        }
        header h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 5vw, 3.2rem);
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        header p { color: #94a3b8; font-size: 0.95rem; margin: 4px 0; }
        header a { color: #60a5fa; text-decoration: none; font-weight: 500; }
        header a:hover { text-decoration: underline; }

        /* ---- Layout ---- */
        .container { max-width: 960px; margin: auto; padding: 30px 20px 60px; }

        .card {
            background: var(--white);
            padding: 28px 30px;
            margin-bottom: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        .card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--dark);
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-light);
        }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 680px) { .grid { grid-template-columns: 1fr; } }

        /* ---- Lists ---- */
        ul { padding-left: 20px; }
        ul li { margin-bottom: 5px; font-size: 0.95rem; }

        /* ---- Form ---- */
        .form-group { margin-bottom: 14px; }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--mid);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            color: var(--dark);
            transition: border-color 0.2s;
            background: #fafcff;
        }
        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
        }
        textarea { resize: vertical; min-height: 90px; }

        button[type="submit"] {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: background 0.2s, transform 0.1s;
        }
        button[type="submit"]:hover { background: #1558c7; }
        button[type="submit"]:active { transform: scale(0.98); }

        /* ---- Feedback ---- */
        .feedback {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 16px;
        }
        .feedback.success { background: var(--success-bg); color: var(--success-text); }
        .feedback.error   { background: var(--error-bg);   color: var(--error-text); }

        /* ---- Project entries ---- */
        .project-entry {
            border-left: 3px solid var(--primary);
            padding: 12px 16px;
            margin-bottom: 14px;
            background: var(--primary-light);
            border-radius: 0 8px 8px 0;
        }
        .project-entry strong { font-size: 1rem; }
        .project-entry .tech-tag {
            display: inline-block;
            background: var(--primary);
            color: white;
            font-size: 0.72rem;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
            vertical-align: middle;
            font-weight: 500;
        }
        .project-entry p { color: var(--mid); font-size: 0.9rem; margin-top: 6px; }

        /* ---- GitHub repos ---- */
        .api-item {
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }
        .api-item:last-child { border-bottom: none; }
        .api-item strong { font-size: 1rem; }
        .api-item small { color: #64748b; font-size: 0.82rem; }
        .api-item a { color: var(--primary); text-decoration: none; font-weight: 500; font-size: 0.9rem; }
        .api-item a:hover { text-decoration: underline; }

        .loading { color: #94a3b8; font-style: italic; font-size: 0.9rem; }
    </style>
</head>
<body>

<header>
    <h1>Nandana A K</h1>
    <p>BCA Student &mdash; Kristu Jayanti University</p>
    <!-- FIX: Phone number removed from public HTML to avoid bot scraping -->
    <p>
        GitHub: <a href="https://github.com/nandanaak2306-sys" target="_blank" rel="noopener">nandanaak2306-sys</a>
        &nbsp;|&nbsp;
        Email: <a href="mailto:nandana.ak2306@gmail.com">nandana.ak2306@gmail.com</a>
    </p>
</header>

<div class="container">

    <div class="card">
        <h2>About Me</h2>
        <p>I am a second-year BCA student at Kristu Jayanti University with a strong foundation in software development. I have hands-on experience building web applications using Java and Spring Boot. I am a fast learner and an effective problem solver, currently maintaining an SGPA of 8.96.</p>
    </div>

    <div class="grid">
        <div class="card">
            <h2>Education &amp; Stats</h2>
            <p><strong>BCA</strong> &mdash; Kristu Jayanti University</p>
            <p><strong>SGPA:</strong> 8.96 / 10</p>
            <h3 style="margin: 16px 0 8px; font-size: 1rem;">Certifications</h3>
            <ul>
                <li>Git Guide &mdash; Infosys Springboard</li>
                <li>Programming in Java &mdash; NPTEL</li>
                <li>MongoDB Certification</li>
                <li>Digital Engineering &mdash; Future Skills</li>
            </ul>
        </div>

        <div class="card">
            <h2>Add New Project</h2>

            <?php if ($feedback_message): ?>
                <div class="feedback <?= htmlspecialchars($feedback_type) ?>">
                    <?= htmlspecialchars($feedback_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <!-- FIX: CSRF token hidden field -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="title">Project Title</label>
                    <input type="text" id="title" name="title" placeholder="e.g. Student Portal" maxlength="150" required>
                </div>
                <div class="form-group">
                    <label for="tech">Technologies Used</label>
                    <input type="text" id="tech" name="tech" placeholder="e.g. PHP, MySQL, Bootstrap" maxlength="200" required>
                </div>
                <div class="form-group">
                    <label for="description">Short Description</label>
                    <textarea id="description" name="description" placeholder="What does this project do?" maxlength="1000" required></textarea>
                </div>
                <button type="submit" name="save_project">Save to Database</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>GitHub Repositories (Live)</h2>
        <div id="github-repos" class="loading">Loading repositories&hellip;</div>
    </div>

    <div class="card">
        <h2>Project Gallery</h2>
        <?php
        // FIX: Check if DB connection is valid before querying
        if (!$conn || $conn->connect_error) {
            echo "<p style='color:red;'>Database connection failed. Cannot load projects.</p>";
        } else {
            // FIX: Use prepared statement and check result
            $res = $conn->query("SELECT * FROM project_details ORDER BY id DESC");
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    echo "<div class='project-entry'>";
                    echo "<strong>" . htmlspecialchars($row['title']) . "</strong>";
                    echo "<span class='tech-tag'>" . htmlspecialchars($row['tech_stack']) . "</span>";
                    echo "<p>" . htmlspecialchars($row['description']) . "</p>";
                    echo "</div>";
                }
            } else {
                echo "<p style='color:#64748b;'>No projects stored yet. Use the form above to add your first project.</p>";
            }
        }
        ?>
    </div>

</div>

<script>
    // FIX: Fetch repos sorted by last updated; show top 4 instead of arbitrary 3
    async function fetchRepos() {
        const container = document.getElementById('github-repos');
        try {
            const response = await fetch(
                'https://api.github.com/users/nandanaak2306-sys/repos?sort=updated&direction=desc&per_page=10'
            );
            if (!response.ok) throw new Error('GitHub API error');

            const repos = await response.json();
            container.innerHTML = '';
            container.classList.remove('loading');

            if (!repos.length) {
                container.textContent = 'No public repositories found.';
                return;
            }

            repos.slice(0, 4).forEach(repo => {
                const div = document.createElement('div');
                div.className = 'api-item';
                div.innerHTML = `
                    <strong>${repo.name}</strong><br>
                    <small>Language: ${repo.language || 'N/A'} &nbsp;|&nbsp; ⭐ ${repo.stargazers_count} &nbsp;|&nbsp; Updated: ${new Date(repo.updated_at).toLocaleDateString()}</small><br>
                    <a href="${repo.html_url}" target="_blank" rel="noopener">View Repo →</a>
                `;
                container.appendChild(div);
            });

        } catch (error) {
            container.textContent = 'Failed to load GitHub data. Please try again later.';
            container.classList.remove('loading');
        }
    }

    fetchRepos();
    // FIX: Removed console.log from production code
</script>

</body>
</html>