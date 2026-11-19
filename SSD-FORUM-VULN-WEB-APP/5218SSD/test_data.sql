-- Optional demo data. Import AFTER sql.sql.
USE `ssd_forum`;

-- Demo user (password: Demo123!) - local testing only
INSERT INTO `users` (`userId`, `userName`, `email`, `dob`, `password`) VALUES
(1, 'demo', 'demo@example.com', '2000-01-01', '$2y$10$YUOTAB16Zxc2FyRYgmfj0O/FdPp69bmfFnlOBDLss462stqsVuVM6');

-- Demo posts
INSERT INTO `posts` (`userId`, `title`, `content`) VALUES
(1, 'Welcome to SSD Forum', 'This is the first post on the forum.'),
(1, 'SQL Injection', 'This post explains how SQL injection works and how it can be prevented.'),
(1, 'Password Security', 'This post explains hashing passwords using bcrypt.'),
(1, 'CSRF Protection', 'Cross-site request forgery and how to prevent it using tokens.'),
(1, 'File Upload Security', 'Discussion about secure file upload practices.');
