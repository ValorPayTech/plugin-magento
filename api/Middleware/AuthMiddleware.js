// const jwt = require("jsonwebtoken");

module.exports = (req, res, next) => {
    const authorizationHeader = req.headers.authorization;
    let token;

    if (authorizationHeader) {
        token = authorizationHeader.split(" ")[1];
    }
    try {
        if (token) {
            
        } else {
            res.status(403).json({
                error: "No token provided",
            });
        }
    } catch (error) {
        console.log(error);
        res.status(500).json({ error: true });
    }
};
