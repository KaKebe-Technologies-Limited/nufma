// Database

const express = require('express');
const multer = require('multer');
const mysql = require('mysql2/promise');

const app = express();
const PORT = process.env.PORT || 5000;

// Database connection
const db = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '101619',
    database: 'film_submissions'
});

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Configure file upload
const storage = multer.diskStorage({
    destination: './uploads/',
    filename: (req, file, cb) => {
        cb(null, Date.now() + '-' + file.originalname);
    }
});
const upload = multer({ storage });

// Route to handle submissions
app.post('/submit', upload.single('file'), async (req, res) => {
    try {
        const { name, company, email, category, projectLink, summary } = req.body;
        const file = req.file ? req.file.filename : null;

        const connection = await db.getConnection();
        await connection.query(
            'INSERT INTO submissions (name, company, email, category, file, projectLink, summary) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [name, company, email, category, file, projectLink, summary]
        );
        connection.release();

        res.status(201).json({ message: 'Submission successful' });
    } catch (error) {
        res.status(500).json({ message: 'Error submitting', error: error.message });
    }
});

// Start server
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
