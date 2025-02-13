<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Quizz;
use App\Entity\Question;
use App\Entity\Answer;
use App\Repository\QuizzRepository;

final class QuizzController extends AbstractController
{

    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/quizz', name: 'app_quizz')]
    public function index(): Response
    {
        return $this->render('quizz/index.html.twig', [
            'controller_name' => 'QuizzController',
        ]);
    }


    #[Route('/quizz/create', name: 'app_quizz_create')]
    public function create(): Response
    {
        return $this->render('quizz/create.html.twig', [
            'controller_name' => 'QuizzController',
        ]);
    }

    #[Route('/quizz/play/{id}', name: 'app_quizz_play')]
    public function play($id): Response{
        return $this->render('quizz/play.html.twig', [
            'controller_name' => 'QuizzController',
            'id' => $id
        ]);
    }

    #[Route('/quizz/generate', name: 'app_quizz_generate')]
    public function generate() : Response{
        return $this->render('quizz/generate.html.twig', [
            'controller_name' => 'QuizzController',
        ]);
    }

    #[Route('/quizz/generateQuizz', name: 'generate_quizz', methods: ['POST'])]
public function generateQuizz(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $requestData = json_decode($request->getContent(), true);
    $prompt = "Genere moi un quizz de difficulté " . $requestData['difficulty'] . " sur le theme " . $requestData['prompt'] . " je veux la reponse de la forme json : [{question:question1, answers:[firstAnswer, secondAnswer...], finalAnswerIndex:x}] Il me faut 4 reponse par question et " . $requestData['nbQuestion'] . " questions";
    
    $googleApiKey = $_ENV['GEMINI_API_KEY'];
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$googleApiKey}";

    $response = $this->httpClient->request('POST', $url, [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'contents' => [
                [
                    'parts' => [['text' => $prompt]],
                ],
            ],
        ],
    ]);

    $data = $response->toArray();
    $textResponse = $data['candidates'][0]['content']['parts'][0]['text'];

    $textResponse = preg_replace('/^```json|```$/m', '', $textResponse);

    $generatedQuestions = json_decode(trim($textResponse), true);

    if (!$generatedQuestions) {
        return new JsonResponse(['error' => 'Erreur lors du décodage des questions'], 500);
    }


    // Création du quiz
    $quizz = new Quizz();
    $quizz->setName("Quizz sur " . $requestData['prompt']);
    $quizz->setDescription("Un quizz généré sur le thème " . $requestData['prompt'] . " avec une difficulté " . $requestData['difficulty']);
    
    $entityManager->persist($quizz);
    
    foreach ($generatedQuestions as $generatedQuestion) {
        $question = new Question();
        $question->setDescription($generatedQuestion['question']);
        $question->setDifficulty($requestData['difficulty']);
        $question->setQuizz($quizz);

        $entityManager->persist($question);

        foreach ($generatedQuestion['answers'] as $index => $answerText) {
            $answer = new Answer();
            $answer->setValue($answerText);
            $answer->setIsCorrect($index === $generatedQuestion['finalAnswerIndex']);
            $answer->setQuestion($question);

            $entityManager->persist($answer);
        }
    }

    $entityManager->flush();

    return new JsonResponse($data, 201);
}

}
