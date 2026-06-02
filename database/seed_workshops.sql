-- SQL Seeding Script: Insert 15 Sample Workshops across 15 domains
USE `seminar_portal`;

-- Empty workshops table first to ensure clean state
TRUNCATE TABLE `workshops`;

INSERT INTO `workshops` 
(`title`, `domain`, `host`, `speaker`, `description`, `prerequisites`, `capacity`, `seats_remaining`, `start_date`, `end_date`, `venue`, `poster_image`, `status`) 
VALUES
(
    'Deep Learning Foundations & Transformers', 
    'AI & ML', 
    'Department of Artificial Intelligence', 
    'Dr. Arpan Mukherjee (Senior AI Researcher)', 
    'Dive deep into neural network foundations, backpropagation, and transformer models. Perfect for understanding the technology behind modern Large Language Models.', 
    'Basic Python programming and knowledge of Linear Algebra.', 
    60, 55, '2026-06-10', '2026-06-12', 'Seminar Hall B', NULL, 'active'
),
(
    'Practical Data Science with Pandas & SQL', 
    'Data Science', 
    'Center for Data Analytics', 
    'Mrs. Shruti Gupta (Lead Data Scientist, Analytica)', 
    'Learn how to clean, aggregate, analyze, and visualize high-dimensional datasets using python pandas, seaborn, and optimized SQL queries.', 
    'Basic knowledge of programming and databases.', 
    80, 80, '2026-06-14', '2026-06-15', 'CSE Lab 4', NULL, 'active'
),
(
    'Penetration Testing & Network Security Defense', 
    'Cybersecurity', 
    'Information Security Group', 
    'Mr. Rohit Sen (EC-Council Certified Ethical Hacker)', 
    'Hands-on workshop exploring common cybersecurity vulnerabilities, system scanning, firewalls, and network protection policies using Kali Linux.', 
    'Understanding of basic computer networking protocols.', 
    50, 48, '2026-06-18', '2026-06-20', 'Digital Forensic Lab', NULL, 'active'
),
(
    'Architecting Serverless Solutions on AWS', 
    'Cloud Computing', 
    'AWS Campus Club', 
    'Mrs. Priya Verma (AWS Solutions Architect)', 
    'A practical tutorial on serverless architectures, lambda functions, API gateway routing, and hosting scalable web portals on Amazon Web Services.', 
    'Basic understanding of web technologies and API usage.', 
    100, 95, '2026-06-22', '2026-06-23', 'Main Seminar Hall', NULL, 'active'
),
(
    'Building Smart Cities with IoT Microcontrollers', 
    'IoT', 
    'Department of Electronics', 
    'Dr. Sameer Alvi (IoT Systems Lead)', 
    'Hands-on session using ESP32 chips, analog sensors, and MQTT brokers to configure local automated node grids and publish telemetry statistics.', 
    'Basic concepts of electrical circuits and C++ logic.', 
    45, 42, '2026-06-25', '2026-06-27', 'Embedded Electronics Lab', NULL, 'active'
),
(
    'Embedded Systems Coding with STM32 Chips', 
    'Embedded Systems', 
    'Microelectronics Research Cell', 
    'Mr. Kevin Peters (Hardware Architect)', 
    'Master hardware interrupt routines, custom registers, direct memory access (DMA) configurations, and SPI bus protocols using STM32 C++ boards.', 
    'Familiarity with C/C++ programming and pointer variables.', 
    40, 40, '2026-06-28', '2026-06-30', 'Robotics Innovation Center', NULL, 'active'
),
(
    'Modern Fullstack Architecture with React & Node', 
    'Web Development', 
    'Web Creators Group', 
    'Prof. S. Sharma (Lead Engineer, DevSuite)', 
    'A comprehensive coding workshop building and deploying single-page reactive web views connected to NodeJS Express routes and relational databases.', 
    'Familiarity with basic HTML, CSS, and fundamental JavaScript.', 
    120, 110, '2026-07-02', '2026-07-04', 'CSE Lab 2', NULL, 'active'
),
(
    'CI/CD Pipelines with Docker & Kubernetes', 
    'DevOps', 
    'Cloud Infrastructure Group', 
    'Mr. Anand Rao (DevOps Team Lead, TechForge)', 
    'Learn how to package backend services into isolated containers, handle routing, and deploy self-healing multi-node services using Kubernetes clusters.', 
    'Understanding of standard Linux commands and terminal scripting.', 
    75, 75, '2026-07-06', '2026-07-07', 'Advanced Computing Lab', NULL, 'active'
),
(
    'Smart Contract Development on Ethereum', 
    'Blockchain', 
    'Decentralized Tech Lab', 
    'Dr. Vivek Singhal (Blockchain Researcher)', 
    'Author and deploy secure solidity smart contracts on testnet grids, addressing gas limits, security audits, and decentralized web connections.', 
    'Strong software logic and basic object-oriented concepts.', 
    60, 58, '2026-07-10', '2026-07-12', 'Seminar Hall C', NULL, 'active'
),
(
    'ASIC Design Flow & FPGA Prototyping', 
    'VLSI', 
    'VLSI Design Lab', 
    'Mrs. Shalini Iyer (Senior VLSI Design Engineer)', 
    'Explore front-end digital IC designs using Verilog HDL, RTL synthesis validation, physical floorplanning, and mapping code onto FPGA prototypes.', 
    'Familiarity with digital electronics logic and logic gates.', 
    35, 35, '2026-07-14', '2026-07-16', 'Microelectronics Lab', NULL, 'active'
),
(
    'RISC-V ISA Instruction Set & Pipeline Design', 
    'Computer Architecture', 
    'Advanced Architecture Cell', 
    'Dr. J. P. Nair (Dean, Computer Systems)', 
    'Analyze assembly instruction mappings, pipelining hazards, out-of-order execution, branch prediction, and customized hardware blocks in RISC-V.', 
    'Familiarity with computer organization and hardware logic.', 
    50, 48, '2026-07-18', '2026-07-19', 'CSE Conference Hall', NULL, 'active'
),
(
    'Autonomous Mobile Robots with ROS (Robot Operating System)', 
    'Robotics', 
    'Department of Mechatronics', 
    'Dr. Marc Dupont (Robotics Professor)', 
    'Learn SLAM (Simultaneous Localization and Mapping), LiDAR sensor integration, and path-planning algorithms using ROS and virtual simulator systems.', 
    'Understanding of Linux bash and intermediate Python logic.', 
    40, 38, '2026-07-22', '2026-07-24', 'Robotics Innovation Center', NULL, 'active'
),
(
    'Immersive AR/VR Experiences with Unity & WebXR', 
    'AR/VR', 
    'Interactive Media Lab', 
    'Ms. Sarah Jenkins (Creative Technologist)', 
    'Learn virtual viewport mapping, camera rigs, hand-tracking interaction widgets, and deploying virtual rooms via Unity engine and standard WebXR.', 
    'Basic knowledge of C# or JavaScript.', 
    55, 55, '2026-07-26', '2026-07-27', 'Media Studio Hall', NULL, 'active'
),
(
    'User-Centered Design & Interactive Figma Prototyping', 
    'UI/UX', 
    'Creative Arts Division', 
    'Mr. Neil Patel (Lead Product Designer, DesignHub)', 
    'Learn standard design principles, wireframing, building reusable design systems, conducting usability tests, and compiling interactive Figma mockups.', 
    'None. Open to all students interested in product design.', 
    90, 85, '2026-07-29', '2026-07-30', 'Seminar Hall A', NULL, 'upcoming'
),
(
    'Cross-Platform Mobile Apps with Flutter', 
    'Mobile Development', 
    'App Development Club', 
    'Mr. Tushar Saxena (Mobile Architect)', 
    'A quick-start workshop implementing state management, native layouts, hardware API integration, and standard responsive interfaces using Dart.', 
    'Object-oriented programming concepts (Java, C++, or JS).', 
    70, 70, '2026-08-02', '2026-08-04', 'CSE Lab 3', NULL, 'upcoming'
);
