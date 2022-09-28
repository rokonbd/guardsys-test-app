<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Mail\SuspendedUserMail;
use Illuminate\Support\Facades\Mail;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {        
            $listOfUser = User::all();
        
            return $this->sendSuccess(UserResource::collection($listOfUser), 'User list has been retrieved successfully!');
        } catch (Exception $e) {
            return $this->sendError('Caught exception: ',  $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return $this->sendError('Not Found!');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            $inputData = $request->all();
            $validator = Validator::make($inputData, [
                'name'      => 'required|max:255',
                'email'     => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')
                ],
                'phone'      => 'required|max:255',
                'photo'      => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240'
            ]);
            if($validator->fails()){
                return $this->sendError('Validation Error!', $validator->errors());       
            }

            $newUser = new User();
            $newUser->photo = '';
            // upload user profile image 
            if(isset($inputData['photo']) && !empty($inputData['photo'])){
                $photo = $inputData['photo'];
                $photoName = date('YmdHi').$photo->getClientOriginalName();
                $photo->move(public_path('storage/'), $photoName);
                $newUser->photo = $photoName;
            }
            $newUser->fill($request->input());

            if($newUser->save()){
                //sent sms after create a user
                sent_sms_for_new_user($newUser->phone);
                return $this->sendSuccess(new UserResource($newUser), 'User has been created successfully!');
            } else {
                return $this->sendError('User has not been created successfully!', new UserResource($newUser));
            }
        } catch (Exception $e) {
            return $this->sendError('Caught exception: ',  $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $singleUser = User::find($id);
            if (empty($singleUser)) {
                return $this->sendError('Your requested user not found!');
            }
    
            return $this->sendSuccess(new UserResource($singleUser), 'The user information has been retrieved successfully!');
        } catch (Exception $e) {
            return $this->sendError('Caught exception: ',  $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return $this->sendError('Not Found!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            if(empty($id)){
                return $this->sendError('Not Found!');
            }
            $oldUser = User::find($id);
            if(empty($oldUser)){
                return $this->sendError('Not Found!');
            }
            $inputData = $request->all();
            $validator = Validator::make($inputData, [
                'name'      => 'required|max:255',
                'email'     => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($oldUser->id)
                ],
                'phone'      => 'required|max:255',
                'photo'      => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240'
            ]);
            if($validator->fails()){
                return $this->sendError('Validation Error!', $validator->errors());       
            }
            // upload user profile image. 
            // already use have image then remove previous 
            // image then upload new image. 
            if(isset($inputData['photo']) && !empty($inputData['photo'])){
                $photo = $inputData['photo'];
                $photoName = date('YmdHi').$photo->getClientOriginalName();
                $photo->move(public_path('storage/'), $photoName);
                if(file_exists(public_path('storage/').$oldUser->photo)) {
                    $flag = unlink(public_path('storage/').$oldUser->photo);
                }
                $oldUser->photo = $photoName;
            }
            $oldUser->name = $inputData['name'];
            $oldUser->email = $inputData['email'];
            $oldUser->phone = $inputData['phone'];
            
            // dd($oldUser);
            if($oldUser->save()){
                return $this->sendSuccess(new UserResource($oldUser), 'The user information has been updated successfully!');
            } else {
                return $this->sendError('The user information has not been updated successfully!', new UserResource($oldUser));
            }
        } catch (Exception $e) {
            return $this->sendError('Caught exception: ',  $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            if(empty($id)){
                return $this->sendError('Not Found!');
            }
            $oldUser = User::find($id);
            $deleteUser = $oldUser;
            if(empty($oldUser)){
                return $this->sendError('Not Found!');
            }
            $flag = $deleteUser->delete();
            if(empty($flag)){
                $msg = "This item has not been deleted successfully!";            
                return $this->sendError($flag, $msg);
            } else {   
                //sent sms after delete user
                sent_sms_for_delete_user($oldUser->phone);
                //sent email after delete user
                Mail::to($oldUser->email)->send(new SuspendedUserMail($oldUser));
                //delete user profile image if it is exists
                if(Storage::disk('local')->exists(public_path('avatar/').$oldUser->photo)) {
                    $flag = Storage::disk('local')->delete(public_path('avatar/').$oldUser->photo);
                }         
                $msg = "This item has been deleted successfully!";
                return $this->sendSuccess($oldUser, $msg);
            }
        } catch (Exception $e) {
            return $this->sendError('Caught exception: ',  $e->getMessage());
        }
    }
}
