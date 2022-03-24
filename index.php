<?php

/* Подключаемся к БД (имя сервера, имя пользователя БД, пароль БД, имя БД)*/
$mysqli = new mysqli("localhost", "root", "", "ossetianews");

/* Получаем Xpath главной страницы */
$mainPageXpath = getXpath('https://lenta.ru/tags/geo/severnaya-osetiya');

/* Находим все контейнеры div с классом item (карточки новостей) в контейнере div склассом news-list */
foreach ($mainPageXpath->query(".//ul[contains(@class, 'content-tags-page__body')]//li[contains(@class, 'content-tags-page__item _news')]") as $item) {
  /* Находим DOM-элемент заголовка */
  $title = $mainPageXpath->query(".//h3[contains(@class, 'card-full-news__title')]", $item);
  /* Получаем текстовое содержимое заголовка */
  $titleText = $title[0]->textContent;

  /* Получаем ссылку на новость */
  $link = $mainPageXpath->query(".//a", $item);
  if($link[0] !== null) {
    $newsUrl = $link[0]->getAttribute('href');
  }

  /* Получаем дату новости */
  $date = $mainPageXpath->query(".//div[contains(@class, 'card-full-news__info')]//time", $item);
  $dateText = $date[0]->textContent;
  $time = explode(",", $dateText)[0];
  $dateDB = explode("/", $newsUrl)[2].'-'.explode("/", $newsUrl)[3].'-'.explode("/", $newsUrl)[4];
  $dateTimeDB = $dateDB.' '.$time;  

  /* Получаем Xpath новости */
  $newsText = null;
  $articleXpath = getXpath('https://lenta.ru/'.$newsUrl);

  foreach($articleXpath->query(".//div[contains(@class, 'topic-body__content')]//p") as $articleItem) {
    $newsText .= $articleItem->textContent."\n";
  }

  foreach($articleXpath->query(".//div[contains(@class, 'topic-page')]") as $articleElement) {

    /* Находим DOM-элемент изображения */
    $image = $articleXpath->query(".//img[contains(@class, 'picture__image')]", $articleElement);
    /* Если элемент не пустой получаем значение атрибута src */
    
    if($image[0] !== null) {
      //Миниатюра 
      $imageTmb = $image[0]->getAttribute('src');               // Ссылка на миниатюру
      //Исходное изображение 
      $imageFull = str_replace('-350x230', '', $imageTmb);  // Ссылка на исходное
    }
  }

  $mysqli->query("INSERT INTO `news`
  (`website_id`, `title`, `date`, `text`, `img`)
  VALUES (4, '{$titleText}', '{$dateTimeDB}', '{$newsText}', '{$imageFull}')");

  echo "<h3>{$titleText}</h3>";
  echo $newsUrl."<br>";
  echo "<date>{$dateTimeDB}<date>";
  echo "<div><img src=\"{$imageTmb}\"></div>";
  echo "<div>{$newsText}</div>";
}

function getXpath($url) {
  /* Получаем исходный код страницы */
  $html = file_get_contents($url);
  $html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");
  /* Класс DOMDocument предназначен для работы с кодом HTML и XML */
  $doc = new DOMDocument();
  /* Загружаем html в класс */
  @$doc->loadHTML($html);
  /* Класс DOMXpath реализует язык запросов XPath к элементам XML-документа */
  $xpath = new DOMXpath($doc);

  return $xpath;
}