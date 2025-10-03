-- Initial Data for Scribble Game

-- Insert Achievements
INSERT INTO achievements (name, description, icon, requirement_type, requirement_value, points_reward) VALUES
('First Steps', 'Play your first game', '👶', 'games_played', 1, 10),
('Getting Started', 'Play 10 games', '🎮', 'games_played', 10, 50),
('Veteran', 'Play 50 games', '🎖️', 'games_played', 50, 200),
('Champion', 'Win your first game', '🏆', 'games_won', 1, 25),
('Winning Streak', 'Win 5 games', '🔥', 'games_won', 5, 100),
('Master Artist', 'Complete 25 drawings', '🎨', 'drawings_made', 25, 150),
('Quick Thinker', 'Guess correctly 50 times', '💡', 'correct_guesses', 50, 100),
('Point Master', 'Earn 1000 total points', '💎', 'total_points', 1000, 250);

-- Insert Word Categories
INSERT INTO word_categories (name, description, icon) VALUES
('Animals', 'All kinds of animals from pets to wildlife', '🐾'),
('Food', 'Delicious food and beverages', '🍕'),
('Objects', 'Everyday objects and items', '🎨'),
('Nature', 'Natural elements and landscapes', '🌿'),
('Sports', 'Sports and physical activities', '⚽'),
('Technology', 'Modern technology and gadgets', '💻');

-- Insert Default Words
INSERT INTO words (word, category_id, difficulty) VALUES
-- Animals (category 1)
('cat', 1, 'easy'),
('dog', 1, 'easy'),
('elephant', 1, 'medium'),
('rabbit', 1, 'easy'),
('zebra', 1, 'medium'),
('kangaroo', 1, 'medium'),
('penguin', 1, 'medium'),
('octopus', 1, 'medium'),
('eagle', 1, 'medium'),
('dragon', 1, 'hard'),
('butterfly', 1, 'medium'),

-- Food (category 2)
('apple', 2, 'easy'),
('banana', 2, 'easy'),
('pizza', 2, 'easy'),
('hamburger', 2, 'medium'),
('sandwich', 2, 'easy'),
('pineapple', 2, 'medium'),

-- Objects (category 3)
('guitar', 3, 'medium'),
('house', 3, 'easy'),
('jacket', 3, 'medium'),
('keyboard', 3, 'medium'),
('laptop', 3, 'medium'),
('notebook', 3, 'easy'),
('piano', 3, 'medium'),
('umbrella', 3, 'easy'),
('violin', 3, 'medium'),
('xylophone', 3, 'hard'),
('camera', 3, 'medium'),
('mirror', 3, 'easy'),
('necklace', 3, 'medium'),
('quilt', 3, 'medium'),
('telescope', 3, 'hard'),
('diamond', 3, 'medium'),
('lighthouse', 3, 'hard'),
('helicopter', 3, 'hard'),

-- Nature (category 4)
('flower', 4, 'easy'),
('tree', 4, 'easy'),
('mountain', 4, 'medium'),
('ocean', 4, 'medium'),
('island', 4, 'medium'),
('waterfall', 4, 'medium'),
('sunset', 4, 'medium'),
('forest', 4, 'medium'),
('garden', 4, 'easy'),
('jungle', 4, 'medium'),
('rainbow', 4, 'medium'),
('volcano', 4, 'medium'),
('mushroom', 4, 'medium'),
('snowman', 4, 'easy'),
('sunflower', 4, 'medium'),

-- Sports (category 5)
('football', 5, 'easy'),
('bicycle', 5, 'medium'),

-- Technology (category 6)
('internet', 6, 'hard'),
('rocket', 6, 'medium'),
('airplane', 6, 'medium'),
('yacht', 6, 'medium');
