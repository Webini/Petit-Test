<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Petit controlleur rapidos
 * @author nico
 */
abstract class RestController extends Controller
{
    /**
     * @var \Symfony\Component\Serializer\Normalizer\ObjectNormalizer
     */
    private $normalizer = null;
    
    /**
     * @var \Symfony\Component\Serializer\Serializer 
     */
    private $serializer = null;
    
    /**
     * @return \Symfony\Component\Serializer\Normalizer\ObjectNormalizer
     */
    protected function getNormalizer()    
    {
        if ($this->normalizer === null) {
            $this->normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
            $this->normalizer->setSerializer($this->getSerializer());
            //$this->normalizer->setCircularReferenceLimit(1);
        }
        
        return $this->normalizer;
    }
    
    /**
     * @return \Symfony\Component\Serializer\Serializer 
     */
    protected function getSerializer()
    {  
        if ($this->serializer === null) {
            $this->serializer = new Serializer([ $this->getNormalizer() ], [ new JsonEncoder() ]);
        }
        
        return $this->serializer;
    }
    
    /**
     * Serialize les data en $format
     * @param mixed $data
     * @param array $ignoredAttributes 
     * @param string $format json
     */
    protected function serialize($data, $ignoredAttributes = [], $format = 'json')
    {
        $this->getNormalizer()->setIgnoredAttributes($ignoredAttributes);
        return $this->getSerializer()->serialize($data, $format);
    }
    
    /**
     * Deserialize les data
     * @param string $data buffer des data serializées
     * @param string $type nom du type
     * @param string $format json
     * @return mixed
     */
    protected function deserialize($data, $type, $format = 'json')
    {
        return $this->getSerializer()->deserialize($data, $type, $format);
    }
    
    /**
     * @return \Symfony\Component\Validator\Validator\RecursiveValidator
     */
    protected function getValidator()
    {
        return $this->get('validator');
    }
    
    /**
     * Normalize un objet || array
     * @param object|array $data
     * @param array $ignoredAttributes
     * @return mixed
     */
    protected function normalize($data, $ignoredAttributes = [])
    {
        if (is_array($data)) {
            $results = [];
            
            foreach ($data as $key => $value) {
                $results[$key] = $this->normalize($value, $ignoredAttributes);  
            }
            
            return $results;
        }
        
        $normalizer = $this->getNormalizer();
        $normalizer->setIgnoredAttributes($ignoredAttributes);
        
        return $normalizer->normalize($data);
    }
    
    /**
     * Retourne une response rest
     * @param array $data
     * @param int $status
     * @param array $headers
     */
    protected function renderRest($data, $status = 200, $headers = [])
    {
        return $this->createJsonResponse([
            'results' => $data
        ], $status, $headers);
    }
    
    /**
     * Retourne une (des) erreurs rest
     * @param array|\Symfony\Component\Form\Form $data
     * @param int $status
     * @param array $headers
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function renderRestErrors($data, $status = 500, $headers = [])
    {   
        if ($data instanceof ConstraintViolationList) {
            $errors = [];
            foreach ($data as $key => $value) {
                $errors[] = [
                    'field' => $value->getPropertyPath(),
                    'message' => $value->getMessage()
                ];
            }
            $data = $errors;
        }
        
        return $this->createJsonResponse([
            'errors' => $data
        ], $status, $headers);
    }
    
    
    /**
     * Créer une nouvelle json response
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function createJsonResponse($data = null, $status = 200, $headers = [])
    {
        return new JsonResponse($data, $status, $headers);
    }
    
}
