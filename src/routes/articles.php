<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Get all articles
$app->get('/articles/{position}', function (Request $request, Response $response)
{
	$position = $request->getAttribute('position');
	try
	{
		//get articles
		$query = "SELECT * FROM articles WHERE position = '{$position}' ORDER BY ordering ASC";
		$db = new db();
		$db = $db->connect();
		$stmt = $db->query($query);
		$articles = $stmt->fetchAll(PDO::FETCH_OBJ);

		//Parsdown
		$Parsedown = new ParsedownExtra();

		foreach ($articles as $articleKey => $article)
		{
			foreach ($article as $valueKey => $value) {
				if (in_array($valueKey, ["article","credits"]))
				{
					$articles[$articleKey]->$valueKey = $Parsedown->text($value);
				}
			}
		}

		//Flush connection
		$db = null;

		//output json
		$response->getBody()->write(json_encode($articles));
	}
	catch (PDOException $e)
	{
		$response->getBody()->write(json_encode('{"error": {"text": '.$e->getMessage().'} }'));
	}

	return $response;
});

//Get one article
$app->get('/article/{id}/{auth}', function (Request $request, Response $response)
{
	if (checkAuth($request->getAttribute('auth')))
	{
		$id = $request->getAttribute('id');

		try
		{
		//get articles
			$query = "SELECT * FROM articles WHERE id = '".$id."'";
			$db = new db();
			$db = $db->connect();
			$stmt = $db->query($query);
			$articles = $stmt->fetch(PDO::FETCH_OBJ);

		//Flush connection
			$db = null;

		//output json
			$response->getBody()->write(json_encode($articles));
		}
		catch (PDOException $e)
		{
			$response->getBody()->write(json_encode('{"error": {"text": '.$e->getMessage().'} }'));
		}

		return $response;
	}
	else
	{
		$response->getBody()->write(json_encode(array("error" => "AUTH_FAIL")));
	}

});

//Get overview
$app->get('/overview', function (Request $request, Response $response)
{
	$position = $request->getAttribute('position');
	try
	{
			//get articles
		$query = "SELECT * FROM articles WHERE show_in_overview = 1 ORDER BY overview_ordering ASC";
		$db = new db();
		$db = $db->connect();
		$stmt = $db->query($query);
		$articles = $stmt->fetchAll(PDO::FETCH_OBJ);


			//Parsdown
		$Parsedown = new ParsedownExtra();

		foreach ($articles as $articleKey => $article)
		{
			foreach ($article as $valueKey => $value) {
				if (in_array($valueKey, ["article","credits"]))
				{
					$articles[$articleKey]->$valueKey = $Parsedown->text($value);
				}
			}
		}

			//Flush connection
		$db = null;

			//output json
		$response->getBody()->write(json_encode($articles));
	}
	catch (PDOException $e)
	{
		$response->getBody()->write(json_encode(array("error" => $e->getMessage())));
	}

	return $response;
});

//Add article
$app->post('/articles/add/{auth}', function (Request $request, Response $response)
{
	if (checkAuth($request->getAttribute('auth')))
	{
		$itemSave = new stdClass;

		foreach ($request->getParsedBody() as $key => $value)
		{
			$itemSave->$key = $request->getParam($key);
		}

		$itemSave->alias = $itemSave->title;

		$query = "SELECT ordering FROM articles WHERE position='". $itemSave->position ."' ORDER BY ordering DESC LIMIT 0,1";
		$db = new db();
		$db = $db->connect();
		$stmt = $db->query($query);
		$highestOrdering = $stmt->fetch(PDO::FETCH_OBJ)->ordering;

		$itemSave->ordering = $highestOrdering + 1;


		try
		{
			$db = new db();
			$db->insert("articles", $itemSave);

			//output json
			$response->getBody()->write('{"notice": {"text": "Article Added"}');
		}
		catch (PDOException $e)
		{
			$response->getBody()->write('{"error": {"text": '.$e->getMessage().'} }');
		}
	}
	else
	{
		$response->getBody()->write(json_encode(array("error" => "AUTH_FAIL")));
	}

	return $response;
});


//Edit article
$app->put('/articles/edit/{id}/{auth}', function (Request $request, Response $response)
{
	if (checkAuth($request->getAttribute('auth')))
	{
		$id = $request->getAttribute('id');

		$itemSave = new stdClass;

		foreach ($request->getParsedBody() as $key => $value)
		{
			$itemSave->$key = $request->getParam($key);
		}

		$itemSave->alias = $itemSave->title;

		try
		{
			$db = new db();
			$db->update("articles", $itemSave, $id);

			//output json
			$response->getBody()->write('{"notice": {"text": "Article Updated"}');
		}
		catch (PDOException $e)
		{
			$response->getBody()->write('{"error": {"text": '.$e->getMessage().'} }');
		}
	}
	else
	{
		$response->getBody()->write(json_encode(array("error" => "AUTH_FAIL")));
	}

	return $response;
});

//Update ordering
$app->put('/articles/ordering/{context}/{auth}', function (Request $request, Response $response)
{
	if (checkAuth($request->getAttribute('auth')))
	{
		$context = $request->getAttribute('context');

		foreach ($request->getParsedBody() as $key => $item)
		{
			$id = $item['id'];

			$itemSave = new stdClass;

			if ($context == "article")
			{
				$itemSave->ordering = $key;
			}

			if ($context == "overview")
			{
				$itemSave->overview_ordering = $key;
			}


			try
			{
				$db = new db();
				$db->update("articles", $itemSave, $id);

			}
			catch (PDOException $e)
			{
				$error = true;
				$response->getBody()->write('{"error": {"text": '.$e->getMessage().'} }');
			}

			if (!$error) {
				$response->getBody()->write('{"notice": {"text": "Ordering Updated"}');
			}
		}
	}
	else
	{
		$response->getBody()->write(json_encode(array("error" => "AUTH_FAIL")));
	}


	return $response;
});


//Delete article
$app->delete('/articles/delete/{id}/{auth}', function (Request $request, Response $response)
{
	if (checkAuth($request->getAttribute('auth')))
	{
		$id = $request->getAttribute('id');

		try
		{
		//get articles
			$query = "DELETE FROM articles WHERE id = {$id}";

			$db = new db();
			$db = $db->connect();
			$stmt = $db->prepare($query);
			$stmt->execute();

		//Flush connection
			$db = null;

		//output json
			$response->getBody()->write('{"notice": {"text": "Article Deleted"}');
		}
		catch (PDOException $e)
		{
			$response->getBody()->write(json_encode('{"error": {"text": '.$e->getMessage().'} }'));
		}
	}
	else
	{
		$response->getBody()->write(json_encode(array("error" => "AUTH_FAIL")));
	}


	return $response;
});

//Delete article
$app->delete('/overview/delete/{id}/{auth}', function (Request $request, Response $response)
{
	if (checkAuth($request->getAttribute('auth')))
	{
		$id = $request->getAttribute('id');

		try
		{
			//get articles
			$query = "UPDATE articles SET show_in_overview = '' WHERE id = {$id}";

			$db = new db();
			$db = $db->connect();
			$stmt = $db->prepare($query);
			$stmt->execute();

			//Flush connection
			$db = null;

			//output json
			$response->getBody()->write('{"notice": {"text": "Article deleted in overview"}');
		}
		catch (PDOException $e)
		{
			$response->getBody()->write(json_encode('{"error": {"text": '.$e->getMessage().'} }'));
		}
	}
	else
	{
		$response->getBody()->write(json_encode(array("error" => "AUTH_FAIL")));
	}

	return $response;
});


//Add file
$app->post('/upload/{auth}', function (Request $request, Response $response)
{
	if (checkAuth($request->getAttribute('auth')))
	{
		$uploadedFiles = $request->getUploadedFiles();
		$directory = $this->get('upload_directory');

		$uploadedFile = $uploadedFiles['file'];
		if ($uploadedFile->getError() === UPLOAD_ERR_OK) {

			$filename = $uploadedFile->getClientFilename();

			$uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

			$response->getBody()->write(json_encode(array("filename" => $filename)));
		}
		else
		{
			$response->getBody()->write(json_encode($uploadedFile->getError()));
		}
		return $response;
	}
	else
	{
		$response->getBody()->write(json_encode(array("error" => "AUTH_FAIL")));
	}
});

//Delete file
$app->delete('/file/delete/{id}/{formfield}/{auth}', function (Request $request, Response $response)
{
	if (checkAuth($request->getAttribute('auth')))
	{
		$directory = $this->get('upload_directory');

		$id = $request->getAttribute('id');
		$formfield = $request->getAttribute('formfield');

		try
		{
			//get filename
			$query = "SELECT ".$formfield." FROM articles WHERE id='". $id ."'";
			$db = new db();
			$db = $db->connect();
			$stmt = $db->query($query);
			$filename = $stmt->fetch(PDO::FETCH_NUM)[0];

			//check if filename is already found
			$query = "SELECT * FROM articles WHERE background_image='". $filename ."' OR video_forward='". $filename ."' OR video_backward='". $filename ."'";
			$db = new db();
			$db = $db->connect();
			$stmt = $db->query($query);
			$foundFiles = $stmt->fetchAll(PDO::FETCH_NUM);

			if (sizeof($foundFiles) == 1)
			{
				unlink($directory . DIRECTORY_SEPARATOR . $filename);
				// $response->getBody()->write($directory . DIRECTORY_SEPARATOR . $filename);
				$response->getBody()->write('{"notice": {"text": "File Deleted"}');
			}
			else
			{
				// $response->getBody()->write(json_encode($foundFiles));
				$response->getBody()->write('{"notice": {"text": "File not deleted, found in other id\'s"}');
			}

			//Flush connection
			$db = null;

			//output json

		}
		catch (PDOException $e)
		{
			$response->getBody()->write(json_encode('{"error": {"text": '.$e->getMessage().'} }'));
		}
	}
	else
	{
		$response->getBody()->write(json_encode(array("error" => "AUTH_FAIL")));
	}

	return $response;
});