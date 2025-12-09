const express = require("express");
const http = require("http");
const { Server } = require("socket.io");
const path = require("path");

const app = express();
const server = http.createServer(app);

// Setup Socket.IO with CORS (important for frontend access)
const io = new Server(server, {
  cors: {
    origin: "*", // allow all origins during development
    methods: ["GET", "POST"]
  }
});

// Serve static files from the project folder (so HTML/CSS/JS are reachable)
// If you prefer to use Apache (XAMPP) to serve the site, you can remove this
// and let Apache serve files while Node only handles Socket.IO on port 3000.
app.use(express.static(path.join(__dirname)));

// Track specialist connection
let specialistSocket = null;

io.on("connection", (socket) => {
  console.log("New client connected:", socket.id);

  // Specialist registers
  socket.on("registerSpecialist", () => {
    specialistSocket = socket;
    console.log("Specialist registered:", socket.id);
  });

  // User sends message
  socket.on("chatMessage", (msg) => {
    if (specialistSocket) {
      specialistSocket.emit("chatMessage", { sender: "user", msg });
    }
  });

  // Specialist replies
  socket.on("specialistMessage", (msg) => {
    socket.broadcast.emit("chatMessage", { sender: "specialist", msg });
  });

  // Handle disconnects
  socket.on("disconnect", () => {
    console.log("Client disconnected:", socket.id);
    if (socket === specialistSocket) {
      specialistSocket = null;
      console.log("Specialist disconnected");
    }
  });
});

const PORT = 3000;
server.listen(PORT, () => {
  console.log(`✅ Server running at http://localhost:${PORT}`);
});
