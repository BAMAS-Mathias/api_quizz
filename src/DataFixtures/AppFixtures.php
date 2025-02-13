<?php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Question;
use App\Entity\Answer;
use App\Entity\Quizz;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $quizz = new Quizz();
            $quizz->setName("Quizz " . $i);
            $quizz->setDescription("Description du quizz");

            for ($j = 1; $j <= 5; $j++) {
                /* Creation des questions */
                $difficultyList = ['easy', 'medium', 'hard'];
                $question = new Question();
                $question->setDescription('Question n°' . $j);
                $question->setDifficulty($difficultyList[array_rand($difficultyList)]);
                
                // Associate question with quiz BEFORE persisting
                $question->setQuizz($quizz);
                $quizz->addQuestion($question);
                $manager->persist($question);

                /* Creation des réponses */
                for ($k = 1; $k <= 4; $k++) {
                    $answer = new Answer();
                    $answer->setValue('Réponse n°' . $k);
                    $answer->setIsCorrect($k === 1);
                    
                    // Set question instead of manually setting ID
                    $answer->setQuestion($question);
                    $manager->persist($answer);
                }
            }

            // Persist quiz after all questions are added
            $manager->persist($quizz);
        }

        // Flush once at the end
        $manager->flush();
    }
}
