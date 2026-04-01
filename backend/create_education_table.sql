-- Create education_articles table
CREATE TABLE IF NOT EXISTS `education_articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `summary` text,
  `image_url` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `published_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_published` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample education articles
INSERT INTO `education_articles` (`slug`, `title`, `category`, `content`, `summary`, `author`) VALUES
('what-is-ra', 'What is Rheumatoid Arthritis?', 'Basics', 
'<h2>Understanding Rheumatoid Arthritis</h2>
<p>Rheumatoid arthritis (RA) is a chronic inflammatory disorder that primarily affects joints. Unlike osteoarthritis, which results from wear and tear, RA is an autoimmune condition where your immune system mistakenly attacks your own body tissues.</p>
<h3>Key Characteristics:</h3>
<ul>
<li>Affects joints symmetrically (both sides of the body)</li>
<li>Causes pain, swelling, and stiffness</li>
<li>Can affect other organs like heart, lungs, and eyes</li>
<li>More common in women than men</li>
</ul>
<h3>Early Signs:</h3>
<ul>
<li>Morning stiffness lasting more than 30 minutes</li>
<li>Tender, warm, swollen joints</li>
<li>Fatigue and fever</li>
<li>Loss of appetite</li>
</ul>',
'Learn about the basics of Rheumatoid Arthritis, its causes, symptoms, and how it affects your body.',
'Dr. Medical Team'),

('nutrition-tips', 'Nutrition for RA Patients', 'Lifestyle', 
'<h2>Eating Well with Rheumatoid Arthritis</h2>
<p>Proper nutrition plays a crucial role in managing RA symptoms and overall health.</p>
<h3>Anti-Inflammatory Foods:</h3>
<ul>
<li><strong>Fatty Fish:</strong> Salmon, mackerel, sardines (rich in omega-3)</li>
<li><strong>Fruits & Vegetables:</strong> Berries, leafy greens, broccoli</li>
<li><strong>Nuts & Seeds:</strong> Walnuts, chia seeds, flaxseeds</li>
<li><strong>Olive Oil:</strong> Extra virgin olive oil</li>
</ul>
<h3>Foods to Limit:</h3>
<ul>
<li>Processed foods high in sugar</li>
<li>Red meat and processed meats</li>
<li>Refined carbohydrates</li>
<li>Excessive alcohol</li>
</ul>
<h3>Hydration:</h3>
<p>Drink plenty of water throughout the day to help reduce inflammation and maintain joint health.</p>',
'Discover the best foods to eat and avoid for managing RA symptoms through proper nutrition.',
'Nutritionist Team'),

('lifestyle', 'Lifestyle Management', 'Lifestyle', 
'<h2>Living Well with RA</h2>
<p>Managing RA involves more than just medication. Lifestyle changes can significantly improve your quality of life.</p>
<h3>Exercise:</h3>
<ul>
<li>Low-impact activities like swimming and walking</li>
<li>Gentle stretching and yoga</li>
<li>Strength training with light weights</li>
</ul>
<h3>Stress Management:</h3>
<ul>
<li>Meditation and mindfulness</li>
<li>Deep breathing exercises</li>
<li>Adequate sleep (7-9 hours)</li>
</ul>
<h3>Joint Protection:</h3>
<ul>
<li>Use assistive devices when needed</li>
<li>Maintain good posture</li>
<li>Take breaks during activities</li>
<li>Apply heat or cold therapy</li>
</ul>',
'Learn practical lifestyle tips for managing RA and improving your daily life.',
'Lifestyle Coach'),

('managing-symptoms', 'Managing Your Symptoms', 'Treatment', 
'<h2>Effective Symptom Management</h2>
<p>Understanding how to manage your RA symptoms can help you maintain an active, fulfilling life.</p>
<h3>Pain Management:</h3>
<ul>
<li>Take medications as prescribed</li>
<li>Use hot/cold therapy</li>
<li>Practice relaxation techniques</li>
<li>Consider physical therapy</li>
</ul>
<h3>Reducing Inflammation:</h3>
<ul>
<li>Follow your treatment plan</li>
<li>Maintain a healthy weight</li>
<li>Eat anti-inflammatory foods</li>
<li>Get regular exercise</li>
</ul>
<h3>When to Contact Your Doctor:</h3>
<ul>
<li>Sudden increase in pain or swelling</li>
<li>New symptoms develop</li>
<li>Medications cause side effects</li>
<li>Difficulty performing daily activities</li>
</ul>',
'Practical strategies for managing RA symptoms and knowing when to seek medical help.',
'Dr. Medical Team');
