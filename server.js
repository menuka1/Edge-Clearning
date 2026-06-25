import express from 'express';
import cors from 'cors';
import nodemailer from 'nodemailer';
import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const app = express();

app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname)));

app.post('/send-email', async (req, res) => {
  const { name, phone, email, service, date, message } = req.body;

  if (!name || !phone || !email || !service) {
    return res.status(400).json({ error: 'Missing required fields.' });
  }

  const transporter = nodemailer.createTransport({
    host: 'smtp.gmail.com',
    port: 465,
    secure: true,
    auth: {
      user: 'r.indikasilva@gmail.com',
      pass: process.env.EMAIL_PASSWORD,
    },
  });

  const mailOptions = {
    from: 'Edge Cleaning <r.indikasilva@gmail.com>',
    to: 'info.edgecleaning6@gmail.com',
    subject: 'New cleaning request from website',
    text: `Name: ${name}\nPhone: ${phone}\nEmail: ${email}\nService: ${service}\nPreferred Date: ${date || 'N/A'}\nDetails: ${message || 'N/A'}`,
  };

  try {
    await transporter.sendMail(mailOptions);
    res.json({ success: true });
  } catch (error) {
    console.error('Email send error:', error);
    res.status(500).json({ error: 'Could not send email' });
  }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});
