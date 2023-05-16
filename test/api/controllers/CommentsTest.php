<?php
require_once __DIR__ . '/../Mocks.php';
use RedBeanPHP\R;

/**
 * @group single
 */
class CommentsTest extends PHPUnit\Framework\TestCase {
  private $comments;

  public static function setUpBeforeClass(): void {
    try {
      R::setup('sqlite:tests.db');
    } catch (Exception $ex) { }
  }

  public function setUp(): void {
    R::nuke();
    Auth::CreateInitialAdmin(new LoggerMock());

    $this->comments = new Comments(new LoggerMock());
  }

  public function testGetComment() {
    $this->createComment();

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt()];

    $args = [];
    $args['id'] = 1;

    $actual = $this->comments->getComment($request, new ResponseMock(), $args);
    $this->assertEquals('success', $actual->body->data->status);
    $this->assertEquals(2, count($actual->body->data->data));
  }

  public function testGetCommentNotFound() {
    $request = new RequestMock();
    $request->header = [DataMock::GetJwt()];

    $args = [];
    $args['id'] = 1;

    $actual = $this->comments->getComment($request, new ResponseMock(), $args);
    $this->assertEquals('No comment found for ID 1.',
      $actual->body->data->alerts[0]['text']);
  }

  public function testGetCommentForbidden() {
    $this->createComment();
    DataMock::CreateBoardAdminUser();

    $args = [];
    $args['id'] = 1;

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt(2)];

    $this->comments = new Comments(new LoggerMock());

    $actual = $this->comments->getComment($request, new ResponseMock(), $args);
    $this->assertEquals('Access restricted.',
      $actual->body->data->alerts[0]['text']);
  }

  public function testGetCommentUnprivileged() {
    DataMock::CreateUnprivilegedUser();

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt(2)];

    $actual = $this->comments->getComment($request, new ResponseMock(), null);
    $this->assertEquals('Access restricted.',
      $actual->body->data->alerts[0]['text']);
  }

  public function testAddComment() {
    $this->createComment();
    $data = $this->getCommentData();

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt()];
    $request->payload = $data;

    $actual = $this->comments->addComment($request, new ResponseMock(), null);
    $this->assertEquals('success', $actual->body->data->status);
  }

  public function testAddCommentUnprivileged() {
    DataMock::CreateUnprivilegedUser();
    $comment = $this->getCommentData();

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt(2)];
    $request->payload = $comment;

    $actual = $this->comments->addComment($request, new ResponseMock(), null);
    $this->assertEquals('Access restricted.',
      $actual->body->data->alerts[0]['text']);
  }

  public function testAddCommentInvalid() {
    $request = new RequestMock();
    $request->invalidPayload = true;
    $request->header = [DataMock::GetJwt()];

    $actual = $this->comments->addComment($request, new ResponseMock(), null);
    $this->assertEquals('failure', $actual->body->data->status);
    $this->assertEquals('error', $actual->body->data->alerts[0]['type']);
  }

  public function testAddCommentForbidden() {
    $this->createComment();
    DataMock::createBoardAdminUser();
    $comment = $this->getCommentData();

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt(2)];
    $request->payload = $comment;

    $actual = $this->comments->addComment($request, new ResponseMock(), null);
    $this->assertEquals('Access restricted.',
      $actual->body->data->alerts[0]['text']);
  }

  public function testUpdateComment() {
    $this->createComment();

    $comment = $this->getCommentData();
    $comment->id = 1;
    $comment->text = 'updated';

    $args = [];
    $args['id'] = $comment->id;

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt()];
    $request->payload = $comment;

    $response = $this->comments->updateComment($request,
      new ResponseMock(), $args);
    $this->assertEquals('success', $response->body->data->status);
  }

  public function testUpdateCommentInvalid() {
    $request = new RequestMock();
    $request->header = [DataMock::GetJwt()];
    $request->invalidPayload = true;

    $args = [];
    $args['id'] = 1;

    $response = $this->comments->updateComment($request,
      new ResponseMock(), $args);
    $this->assertEquals('error', $response->body->data->alerts[0]['type']);

    $response = $this->comments->updateComment($request,
      new ResponseMock(), null);
    $this->assertEquals('error', $response->body->data->alerts[0]['type']);
  }

  public function testUpdateCommentUnprivileged() {
    DataMock::CreateUnprivilegedUser();

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt(2)];

    $actual = $this->comments->updateComment($request,
      new ResponseMock(), null);
    $this->assertEquals('Access restricted.',
      $actual->body->data->alerts[0]['text']);
  }

  public function testUpdateCommentForbidden() {
    $this->createComment();
    DataMock::createBoardAdminUser();

    $comment = $this->getCommentData();
    $comment->id = 1;
    $comment->text = 'updated';

    $args = [];
    $args['id'] = $comment->id;

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt(2)];
    $request->payload = $comment;

    $actual = $this->comments->updateComment($request,
      new ResponseMock(), $args);
    $this->assertEquals('Access restricted.',
      $actual->body->data->alerts[0]['text']);
  }

  public function testUpdateCommentUserSecurity() {
    $this->createComment();
    DataMock::CreateStandardUser();

    $args = [];
    $args['id'] = 1;

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt(2)];

    $actual = $this->comments->updateComment($request,
      new ResponseMock(), $args);
    $this->assertEquals('Access restricted.',
      $actual->body->data->alerts[0]['text']);
  }

  public function testRemoveComment() {
    $this->createComment();

    $args = [];
    $args['id'] = 1;

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt()];

    $actual = $this->comments->removeComment($request,
      new ResponseMock(), $args);
    $this->assertEquals('success', $actual->body->data->status);
  }

  public function testRemoveCommentUnprivileged() {
    DataMock::CreateUnprivilegedUser();

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt(2)];

    $actual = $this->comments->removeComment($request,
      new ResponseMock(), null);
    $this->assertEquals('Access restricted.',
      $actual->body->data->alerts[0]['text']);
  }

  public function testRemoveCommentInvalid() {
    $request = new RequestMock();
    $request->header = [DataMock::GetJwt()];

    $args = [];
    $args['id'] = 1; // No such comment

    $response = $this->comments->removeComment($request,
      new ResponseMock(), $args);
    $this->assertEquals('failure', $response->body->data->status);
  }

  public function testRemoveCommentForbidden() {
    $this->createComment();
    DataMock::CreateBoardAdminUser();

    $args = [];
    $args['id'] = 1;

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt(2)];

    $actual = $this->comments->removeComment($request,
      new ResponseMock(), $args);
    $this->assertEquals('Access restricted.',
      $actual->body->data->alerts[0]['text']);
  }

  public function testRemoveCommentUserSecurity() {
    $this->createComment();
    DataMock::CreateStandardUser();

    $args = [];
    $args['id'] = 1;

    $this->comments = new Comments(new LoggerMock());

    $request = new RequestMock();
    $request->header = [DataMock::GetJwt(2)];

    $actual = $this->comments->removeComment($request,
      new ResponseMock(), $args);
    $this->assertEquals('Access restricted.',
      $actual->body->data->alerts[0]['text']);
  }

  private function getCommentData() {
    $data = new stdClass();

    $data->text = 'test comment';
    $data->user_id = 1;
    $data->task_id = 1;
    $data->timestamp = time();

    return $data;
  }

  private function createComment() {
    $admin = R::load('user', 1);

    $comment = R::dispense('comment');
    R::store($comment);

    $category = R::dispense('category');
    $category->name = 'cat';
    R::store($category);

    $task = R::dispense('task');
    $task->xownCommentList[] = $comment;
    $task->sharedUserList[] = $admin;
    $task->sharedCategoryList[] = $category;

    $column = R::dispense('column');
    $column->xownTaskList[] = $task;

    $board = R::dispense('board');
    $board->xownColumnList[] = $column;
    $board->sharedUserList[] = $admin;

    R::store($board);
  }
}

